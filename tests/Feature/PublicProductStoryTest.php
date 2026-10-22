<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Distribution\OilProduct;
use App\Models\Production\Farm;
use App\Models\User;
use App\Services\Consumer\ConsumerTrace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicProductStoryTest extends TestCase
{
    use RefreshDatabase;

    private function publishedProduct(): array
    {
        $product = OilProduct::factory()->create(['name' => 'Public origin bottle']);
        $requestId = DB::table('oil_lots')->where('id', $product->oil_lot_id)->value('mill_request_id');
        $harvestId = DB::table('mill_requests')->where('id', $requestId)->value('harvest_id');
        $farm = Farm::findOrFail(DB::table('harvests')->where('id', $harvestId)->value('farm_id'));
        $farm->update(['is_public' => true, 'name' => 'Published Orchard', 'olive_variety' => 'Rare Chetoui',
            'gps_lat' => 34.798765, 'gps_lng' => 10.654321]);
        $farm->producerProfile->update(['is_public' => true, 'phone' => 'PRIVATE-CONTACT-555']);

        return [$product, $farm, $requestId, $harvestId];
    }

    public function test_catalog_searches_and_filters_only_published_active_origins(): void
    {
        [$product, $farm] = $this->publishedProduct();
        OilProduct::factory()->create(['name' => 'Other origin bottle']);

        foreach (['Published Orchard', 'Rare Chetoui'] as $search) {
            $this->get(route('catalog.index', ['search' => $search]))->assertOk()
                ->assertSee($product->name)->assertDontSee('Other origin bottle');
        }
        $this->get(route('catalog.index', ['region' => 'Sfax']))->assertOk()
            ->assertSee($product->name)->assertDontSee('Other origin bottle');

        $farm->producerProfile->update(['is_public' => false]);
        $this->get(route('catalog.index', ['search' => 'Published Orchard']))->assertOk()->assertDontSee($product->name);
        $this->get(route('catalog.index', ['region' => 'Sfax']))->assertOk()->assertDontSee($product->name);
        $this->get(route('catalog.index'))->assertOk()->assertSee($product->name)
            ->assertDontSee('Published Orchard')->assertDontSee('Explore these origins on the map');

        $farm->producerProfile->update(['is_public' => true]);
        $farm->producerProfile->user->forceFill(['is_active' => false])->save();
        $this->get(route('catalog.index', ['region' => 'Sfax']))->assertOk()->assertDontSee($product->name);
    }

    public function test_public_maps_use_region_reference_points_and_never_exact_farm_gps(): void
    {
        [$product] = $this->publishedProduct();
        foreach ([route('catalog.index'), route('trace.show', $product->slug)] as $url) {
            $this->get($url)->assertOk()->assertSee('Load origin map')
                ->assertSee('approximate governorate reference points')
                ->assertDontSee('34.798765')->assertDontSee('10.654321')->assertDontSee('PRIVATE-CONTACT-555');
        }
        $point = app(ConsumerTrace::class)->forSlug($product->slug)['origin_map'][0];
        $this->assertSame(config('public-origin-regions.Sfax.lat'), $point['lat']);
        $this->assertSame(config('public-origin-regions.Sfax.lng'), $point['lng']);
    }

    public function test_story_resolves_operation_only_lots_and_displays_public_metrics_without_private_notes(): void
    {
        [$product, $farm, $requestId, $harvestId] = $this->publishedProduct();
        DB::table('harvests')->where('id', $harvestId)->update(['method' => 'trunk_shaker']);
        $operationId = DB::table('milling_operations')->insertGetId([
            'mill_request_id' => $requestId, 'status' => 'completed', 'received_quantity_kg' => 800,
            'output_volume_l' => 160, 'extraction_rate' => 20, 'olive_condition' => 'good',
            'completed_at' => now(), 'notes' => 'PRIVATE-MILL-NOTE', 'delivery_notes' => 'PRIVATE-DELIVERY-NOTE',
        ]);
        DB::table('oil_lots')->where('id', $product->oil_lot_id)->update(['mill_request_id' => null, 'milling_operation_id' => $operationId]);
        $certRequest = DB::table('certificate_requests')->insertGetId([
            'oil_lot_id' => $product->oil_lot_id, 'producer_user_id' => $farm->producerProfile->user_id, 'status' => 'approved',
        ]);
        DB::table('lab_analyses')->insert([
            'certificate_request_id' => $certRequest, 'lab_user_id' => User::factory()->create(['role' => Role::Laboratory])->id,
            'analysis_date' => now(), 'acidity' => 0.35, 'peroxide_value' => 8.2, 'result' => true, 'notes' => 'PRIVATE-LAB-NOTE',
        ]);
        DB::table('certificates')->insert([
            'certificate_request_id' => $certRequest, 'certificate_number' => 'CERT-STORY-001', 'type' => 'Quality',
            'issue_date' => now()->toDateString(), 'expiry_date' => now()->addYear()->toDateString(), 'status' => 'active',
            'pdf_url' => 'javascript:alert(1)',
        ]);
        $this->get(route('trace.show', $product->slug))->assertOk()
            ->assertSee('Published Orchard')->assertSee('Trunk shaker')->assertDontSee('trunk_shaker')
            ->assertSee('Completed milling')->assertSee('160')->assertSee('Laboratory analysis')->assertSee('0.35%')
            ->assertSee('Passed')->assertSee('Valid certificate')->assertSee('href="#reviews"', false)
            ->assertSee(route('certificates.show', 'CERT-STORY-001'))
            ->assertDontSee('javascript:')->assertDontSee('PRIVATE-MILL-NOTE')->assertDontSee('PRIVATE-LAB-NOTE')
            ->assertDontSee('PRIVATE-DELIVERY-NOTE');

        DB::table('milling_operations')->where('id', $operationId)->update(['status' => 'in_progress']);
        $this->get(route('trace.show', $product->slug))->assertOk()->assertDontSee('Completed milling');
    }
}
