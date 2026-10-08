<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Production\Harvest;
use App\Models\User;
use App\Services\Production\HarvestOilAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HarvestOilAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['harvest-assistant.key' => 'fake-harvest-key', 'harvest-assistant.model' => 'gemini-3.1-flash-lite']);
    }

    private function response(array $overrides = []): array
    {
        return ['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($overrides + [
            'yield_min_percent' => 15, 'yield_max_percent' => 22,
            'explanation' => '<script>alert(1)</script> Planning estimate.',
            'limitations' => 'Maturity and milling efficiency are unknown.',
        ])]]]]]];
    }

    public function test_owner_gets_a_validated_range_without_changing_records_or_exposing_notes(): void
    {
        $harvest = Harvest::factory()->create(['quantity_kg' => 1000, 'notes' => 'private harvest note']);
        $before = $harvest->fresh()->getAttributes();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->response())]);

        $this->actingAs($harvest->farm->producerProfile->user)
            ->post(route('producer.harvests.estimate', $harvest->id))
            ->assertOk()->assertSee('163.8–240.2')->assertSee('Planning estimate.')
            ->assertDontSee('<script>alert(1)</script>', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame($before, $harvest->fresh()->getAttributes());
        $this->assertDatabaseCount('oil_lots', 0);
        Http::assertSent(function ($request) {
            $this->assertTrue($request->hasHeader('x-goog-api-key', 'fake-harvest-key'));
            $facts = json_decode($request['contents'][0]['parts'][0]['text'], true);
            $this->assertSame(1000, $facts['olive_quantity_kg']);
            $this->assertArrayNotHasKey('notes', $facts);
            $this->assertArrayNotHasKey('gps_lat', $facts);
            $this->assertArrayNotHasKey('producer', $facts);

            return ! str_contains($request->url(), 'fake-harvest-key');
        });
    }

    public function test_guests_other_producers_and_other_roles_cannot_request_an_estimate(): void
    {
        Http::fake();
        $harvest = Harvest::factory()->create(['quantity_kg' => 1000]);
        $route = route('producer.harvests.estimate', $harvest->id);
        $this->post($route)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => Role::Producer]))->post($route)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => Role::Consumer]))->post($route)->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_missing_quantity_or_key_never_calls_gemini(): void
    {
        Http::fake();
        $harvest = Harvest::factory()->create(['quantity_kg' => null]);
        $assistant = app(HarvestOilAssistant::class);
        $this->assertFalse($assistant->estimate($harvest)['available']);
        $harvest->quantity_kg = 1000;
        config(['harvest-assistant.key' => null]);
        $this->assertFalse($assistant->estimate($harvest)['available']);
        Http::assertNothingSent();
    }

    public function test_invalid_ranges_and_provider_failures_do_not_become_estimates(): void
    {
        $harvest = Harvest::factory()->create(['quantity_kg' => 1000]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->response(['yield_min_percent' => 30, 'yield_max_percent' => 20]))
            ->push($this->response(['yield_max_percent' => 101]))
            ->push(['error' => ['message' => 'private provider details']], 503)
            ->push([], 429)]);
        $assistant = app(HarvestOilAssistant::class);
        for ($i = 0; $i < 4; $i++) {
            $result = $assistant->estimate($harvest);
            $this->assertFalse($result['available']);
            $this->assertStringNotContainsString('private provider details', $result['message']);
        }
    }

    public function test_admin_can_estimate_and_requests_are_rate_limited(): void
    {
        $harvest = Harvest::factory()->create(['quantity_kg' => 1000]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->response())]);
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.harvests.estimate', $harvest->id))->assertOk();
        }
        $this->post(route('admin.harvests.estimate', $harvest->id))->assertStatus(429);
        Http::assertSentCount(5);
    }
}
