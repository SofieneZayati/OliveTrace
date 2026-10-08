<?php

namespace Tests\Feature;

use App\Contracts\DistributionImpactAssistant;
use App\Enums\Role;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\User;
use App\Services\Distribution\Co2Estimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class DistributionImpactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_co2_estimator_uses_each_transport_factor_and_rounds(): void
    {
        $estimator = app(Co2Estimator::class);
        $expected = ['truck' => 1.19, 'van' => 2.97, 'rail' => 0.36, 'ship' => 0.18, 'air' => 7.12];

        foreach ($expected as $transport => $co2) {
            $this->assertSame($co2, $estimator->estimate(10, $transport, 1000, 750));
        }
        $this->assertSame(0.0, $estimator->estimate(1, 'truck', 1, 750));
        $this->assertSame(2.37, $estimator->estimate(10, 'truck', 2000, 750));
    }

    public function test_co2_estimator_rejects_invalid_inputs(): void
    {
        $estimator = app(Co2Estimator::class);

        foreach ([[0, 'truck', 1, 750], [-1, 'truck', 1, 750], [1, 'truck', 0, 750], [1, 'truck', 1, 0], [1, 'unknown', 1, 750]] as $inputs) {
            try {
                $estimator->estimate(...$inputs);
                $this->fail('Invalid estimator input should throw.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_shipment_saving_computes_co2_and_ignores_submitted_estimate(): void
    {
        $product = OilProduct::factory()->create(['bottle_volume_ml' => 750]);
        $shipment = Shipment::factory()->for($product)->create([
            'distance_km' => 10,
            'quantity_bottles' => 1000,
            'transport_type' => 'truck',
            'co2_estimate' => 99999,
        ]);

        $this->assertSame('1.19', $shipment->fresh()->co2_estimate);

        $shipment->co2_estimate = 99999;
        $shipment->save();
        $this->assertSame('1.19', $shipment->fresh()->co2_estimate);
    }

    public function test_cancelled_shipments_keep_estimates_but_are_excluded_from_product_total(): void
    {
        $product = OilProduct::factory()->create();
        $active = Shipment::factory()->for($product)->create();
        $cancelled = Shipment::factory()->for($product)->create(['status' => 'cancelled']);
        $this->assertNotNull($cancelled->co2_estimate);
        DB::table('shipments')->where('id', $active->id)->update(['co2_estimate' => 4.25]);
        DB::table('shipments')->where('id', $cancelled->id)->update(['co2_estimate' => 100]);

        $this->assertSame(4.25, $product->totalCo2Kg());
    }

    public function test_ai_advice_uses_only_shipment_facts_and_does_not_modify_the_shipment(): void
    {
        $shipment = $this->shipment();
        config([
            'olivetrace-distribution.impact_ai_enabled' => true,
            'farm-assistant.provider' => 'gemini',
            'farm-assistant.model' => 'gemini-3.1-flash-lite',
            'services.gemini.key' => 'test-key',
        ]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [[
            'finishReason' => 'STOP',
            'content' => ['parts' => [['text' => json_encode([
                'summary' => 'This is a route-based estimate.',
                'alternative' => 'Switch to ship for about 85% less.',
            ])]]],
        ]]])]);
        $before = $shipment->fresh()->getAttributes();

        $advice = app(DistributionImpactAssistant::class)->analyze($shipment);

        $this->assertSame('ai', $advice->source);
        $this->assertStringContainsString('85%', $advice->alternative);
        $this->assertSame($before, $shipment->fresh()->getAttributes());
        Http::assertSent(function ($request): bool {
            $this->assertTrue($request->hasHeader('x-goog-api-key', 'test-key'));
            $this->assertStringNotContainsString('test-key', $request->url());
            $input = $request['contents'][0]['parts'][0]['text'];
            $facts = json_decode($input, true);
            $this->assertSame('Sfax', $facts['origin']);
            $this->assertSame('Tunis', $facts['destination']);
            $this->assertArrayNotHasKey('id', $facts);
            $this->assertStringNotContainsString('shipment-owner@example.test', $input);

            return true;
        });
    }

    public function test_invalid_json_http_error_and_timeout_use_rule_based_advice(): void
    {
        $shipment = $this->shipment();
        config([
            'olivetrace-distribution.impact_ai_enabled' => true,
            'farm-assistant.provider' => 'gemini',
            'farm-assistant.model' => 'gemini-3.1-flash-lite',
            'services.gemini.key' => 'test-key',
        ]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'not json']]]]]])
            ->push(['error' => ['message' => 'private details']], 500)]);

        $this->assertSame('fallback', app(DistributionImpactAssistant::class)->analyze($shipment)->source);
        $this->assertSame('fallback', app(DistributionImpactAssistant::class)->analyze($shipment)->source);
        Http::fake(['generativelanguage.googleapis.com/*' => function () {
            throw new ConnectionException('simulated timeout');
        }]);
        $this->assertSame('fallback', app(DistributionImpactAssistant::class)->analyze($shipment)->source);
    }

    public function test_disabled_or_missing_ai_key_uses_fallback_without_http(): void
    {
        $shipment = $this->shipment();
        config([
            'olivetrace-distribution.impact_ai_enabled' => false,
            'farm-assistant.provider' => 'gemini',
            'farm-assistant.model' => 'gemini-3.1-flash-lite',
            'services.gemini.key' => 'test-key',
        ]);
        $this->assertSame('fallback', app(DistributionImpactAssistant::class)->analyze($shipment)->source);
        config(['olivetrace-distribution.impact_ai_enabled' => true, 'services.gemini.key' => null]);
        $this->assertSame('fallback', app(DistributionImpactAssistant::class)->analyze($shipment)->source);
        Http::assertNothingSent();
    }

    public function test_impact_route_is_limited_to_owner_and_admin_view_authorization(): void
    {
        $shipment = $this->shipment();
        $route = route('distributor.shipments.impact', $shipment);
        $this->post($route)->assertRedirect(route('login'));

        $owner = $shipment->distributorProfile->user;
        $this->actingAs($owner)->from(route('distributor.shipments.show', $shipment))->post($route)->assertRedirect();

        $otherDistributor = DistributorProfile::factory()->create()->user;
        $this->actingAs($otherDistributor)->post($route)->assertForbidden();

        $consumer = User::factory()->create(['role' => Role::Consumer]);
        $this->actingAs($consumer)->post($route)->assertForbidden();

        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin)->from(route('admin.shipments.show', $shipment))
            ->post(route('admin.shipments.impact', $shipment))->assertRedirect();
    }

    private function shipment(): Shipment
    {
        $profile = DistributorProfile::factory()->create();
        $profile->user->update(['email' => 'shipment-owner@example.test']);
        $product = OilProduct::factory()->create(['bottle_volume_ml' => 750]);

        return Shipment::factory()->for($product)->for($profile)->create([
            'departure_location' => 'Sfax',
            'destination' => 'Tunis',
            'distance_km' => 270,
            'quantity_bottles' => 100,
            'transport_type' => 'truck',
        ]);
    }
}
