<?php

namespace Tests\Feature;

use App\Contracts\OilLotCertificationStatusProvider;
use App\Contracts\OilLotLookup;
use App\Contracts\ProductRatingSummary;
use App\Data\OilLotSummary;
use App\Data\RatingSummary;
use App\Enums\Role;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_consumer_can_view_the_public_catalog(): void
    {
        $product = OilProduct::factory()->create(['name' => 'Visible catalog olive oil']);

        $this->get(route('catalog.index'))->assertOk()->assertSee($product->name);

        $consumer = User::factory()->state(['role' => Role::Consumer])->create();
        $this->actingAs($consumer)->get(route('catalog.index'))->assertOk()->assertSee($product->name);
    }

    public function test_hidden_and_archived_products_are_never_listed(): void
    {
        $visible = OilProduct::factory()->create(['name' => 'Visible catalog product']);
        $hidden = OilProduct::factory()->hidden()->create(['name' => 'Hidden catalog product']);
        $archived = OilProduct::factory()->archived()->create(['name' => 'Archived catalog product']);

        $response = $this->get(route('catalog.index'))->assertOk()->assertSee($visible->name);
        $response->assertDontSee($hidden->name)->assertDontSee($archived->name);
    }

    public function test_public_html_does_not_expose_internal_ids_emails_or_creator_details(): void
    {
        $producer = User::factory()->state(['role' => Role::Producer])->make([
            'name' => 'Private Producer Name',
            'email' => 'private-catalog-identity@example.test',
        ]);
        $producer->id = 991122;
        $producer->save();
        $product = OilProduct::factory()->create([
            'created_by_user_id' => $producer->id,
            'name' => 'Public Chemlali Bottle',
        ]);
        $product->setAttribute('id', 76543210);
        $product->save();

        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString((string) $product->id, $html);
        $this->assertStringNotContainsString((string) $producer->id, $html);
        $this->assertStringNotContainsString($producer->email, $html);
        $this->assertStringNotContainsString($producer->name, $html);
        $this->assertStringNotContainsString('created_by_user_id', $html);
        $this->assertStringNotContainsString('oil_lot_id', $html);
    }

    public function test_search_volume_filter_and_pagination_work(): void
    {
        $matching = OilProduct::factory()->create([
            'name' => 'Chemlali Family Reserve',
            'brand' => 'Sfax Harvest',
            'bottle_volume_ml' => 1000,
        ]);
        OilProduct::factory()->create([
            'name' => 'Arbequina Bottle',
            'brand' => 'North Grove',
            'bottle_volume_ml' => 500,
        ]);

        $this->get(route('catalog.index', ['search' => 'Sfax Harvest']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee('Arbequina Bottle');
        $this->get(route('catalog.index', ['bottle_volume_ml' => '1000']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee('Arbequina Bottle');

        OilProduct::factory()->count(12)->create();
        $firstPage = $this->get(route('catalog.index'))->assertOk();
        $firstPage->assertSee('Next');
        $secondPage = $this->get(route('catalog.index', ['page' => 2]))->assertOk();
        $this->assertNotSame($firstPage->getContent(), $secondPage->getContent());
    }

    public function test_delivered_filter_and_badge_use_delivered_shipments_only(): void
    {
        $delivered = OilProduct::factory()->create(['name' => 'Delivered Tunisian bottle']);
        $pending = OilProduct::factory()->create(['name' => 'Pending Tunisian bottle']);
        Shipment::factory()->for($delivered)->delivered()->create(['destination' => 'Tunis']);
        Shipment::factory()->for($pending)->planned()->create(['destination' => 'Sousse']);

        $this->get(route('catalog.index', ['delivered' => '1']))
            ->assertOk()
            ->assertSee($delivered->name)
            ->assertSee('Delivered to Tunis')
            ->assertDontSee($pending->name);
    }

    public function test_transport_co2_badge_is_shown_only_when_a_non_cancelled_estimate_exists(): void
    {
        $withShipment = OilProduct::factory()->create(['name' => 'CO2 delivered olive oil', 'bottle_volume_ml' => 750]);
        $withoutShipment = OilProduct::factory()->create(['name' => 'No shipment olive oil']);
        $shipment = Shipment::factory()->for($withShipment)->delivered()->create([
            'distance_km' => 270,
            'quantity_bottles' => 1000,
        ]);
        $cancelledOnly = OilProduct::factory()->create(['name' => 'Cancelled shipment olive oil']);
        Shipment::factory()->for($cancelledOnly)->create(['status' => 'cancelled']);

        $response = $this->get(route('catalog.index'))->assertOk()
            ->assertSee('title="Estimated from distance, transport type and quantity"', false)
            ->assertSee('Transport: '.number_format((float) $shipment->co2_estimate, 1, '.', ',').' kg CO₂');

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'Estimated from distance, transport type and quantity'));
        $this->assertStringNotContainsString('Transport: 0.0 kg CO₂', $html);
        $this->assertStringContainsString($withoutShipment->name, $html);
        $this->assertStringContainsString($cancelledOnly->name, $html);
    }

    public function test_transport_co2_excludes_cancelled_shipments_from_card_total(): void
    {
        $product = OilProduct::factory()->create(['name' => 'Mixed shipment CO2 olive oil']);
        $delivered = Shipment::factory()->for($product)->delivered()->create([
            'distance_km' => 270,
            'quantity_bottles' => 1000,
        ]);
        $cancelled = Shipment::factory()->for($product)->create([
            'status' => 'cancelled',
            'distance_km' => 2700,
            'quantity_bottles' => 1000,
        ]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Transport: '.number_format((float) $delivered->co2_estimate, 1, '.', ',').' kg CO₂')
            ->assertDontSee('Transport: '.number_format((float) ($delivered->co2_estimate + $cancelled->co2_estimate), 1, '.', ',').' kg CO₂');
    }

    public function test_transport_co2_rounds_half_up_consistently(): void
    {
        $product = OilProduct::factory()->create();
        $shipment = Shipment::factory()->for($product)->delivered()->create();
        DB::table('shipments')->where('id', $shipment->id)->update(['co2_estimate' => 32.05]);

        $this->get(route('catalog.index'))->assertOk()->assertSee('Transport: 32.1 kg CO₂');
    }

    public function test_sort_options_order_products_by_name_or_creation_time(): void
    {
        $older = OilProduct::factory()->create(['name' => 'Zulu olive oil']);
        $newer = OilProduct::factory()->create(['name' => 'Alpha olive oil']);
        $older->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $this->get(route('catalog.index', ['sort' => 'name']))
            ->assertOk()
            ->assertSeeInOrder(['Alpha olive oil', 'Zulu olive oil']);
        $this->get(route('catalog.index', ['sort' => 'newest']))
            ->assertOk()
            ->assertSeeInOrder(['Alpha olive oil', 'Zulu olive oil']);

        $newer->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();
        $older->forceFill(['created_at' => now()])->saveQuietly();
        $this->get(route('catalog.index', ['sort' => 'newest']))
            ->assertOk()
            ->assertSeeInOrder(['Zulu olive oil', 'Alpha olive oil']);
    }

    public function test_lowest_transport_co2_sort_orders_by_non_cancelled_shipment_estimate(): void
    {
        $higher = OilProduct::factory()->create(['name' => 'Higher emissions olive oil']);
        $lower = OilProduct::factory()->create(['name' => 'Lower emissions olive oil']);
        Shipment::factory()->for($higher)->create(['distance_km' => 2700, 'quantity_bottles' => 1000]);
        Shipment::factory()->for($lower)->create(['distance_km' => 270, 'quantity_bottles' => 1000]);

        $this->get(route('catalog.index', ['sort' => 'lowest_co2']))
            ->assertOk()
            ->assertSeeInOrder(['Lower emissions olive oil', 'Higher emissions olive oil']);
    }

    public function test_invalid_filters_fall_back_to_defaults_without_an_error_page(): void
    {
        $visible = OilProduct::factory()->create(['name' => 'Default filter result']);

        $this->get(route('catalog.index', [
            'search' => str_repeat('x', 130),
            'bottle_volume_ml' => '75',
            'delivered' => 'sometimes',
            'sort' => 'private',
        ]))
            ->assertOk()
            ->assertSee($visible->name)
            ->assertSee('value="newest" selected', false);
    }

    // Post-merge note (Aymen): Module 5 provides the trace.show route, so the
    // catalog always links to the real traceability page. The "coming soon"
    // fallback only applies on branches without Module 5.
    public function test_trace_card_links_to_consumer_trace_page_by_slug(): void
    {
        $product = OilProduct::factory()->create(['name' => 'Traceable olive oil']);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('View traceability')
            ->assertSee('href="'.$product->traceUrl().'"', false);
    }

    public function test_trace_card_becomes_a_real_link_when_trace_route_exists(): void
    {
        Route::get('/trace-test/{slug}', [
            'uses' => fn (string $slug) => $slug,
            'as' => 'trace.show',
        ]);
        $this->assertTrue(Route::has('trace.show'));
        $product = OilProduct::factory()->create(['name' => 'Traceable olive oil']);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('View traceability')
            ->assertSee('href="'.route('trace.show', $product->slug).'"', false);
    }

    public function test_default_rating_hook_is_empty_and_a_bound_summary_displays_stars(): void
    {
        $product = OilProduct::factory()->create(['name' => 'Rated Chemlali oil']);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('★★★★★')
            ->assertSee('Certification not available');

        $this->app->instance(ProductRatingSummary::class, new class implements ProductRatingSummary
        {
            public function summary(int $productId): ?RatingSummary
            {
                return new RatingSummary(4.6, 12);
            }
        });

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('★★★★★')
            ->assertSee('4.6')
            ->assertSee('(12)');
    }

    public function test_certification_status_is_read_through_provider_and_missing_lot_is_safe(): void
    {
        OilProduct::factory()->create(['name' => 'Certified Tunisian olive oil']);
        $this->app->instance(OilLotCertificationStatusProvider::class, new class implements OilLotCertificationStatusProvider
        {
            public function statusFor(int $oilLotId): string
            {
                return 'verified';
            }
        });

        $this->get(route('catalog.index'))->assertOk()->assertSee('Verified');

        $this->app->instance(OilLotLookup::class, new class implements OilLotLookup
        {
            public function find(int $id): ?OilLotSummary
            {
                return null;
            }

            public function exists(int $id): bool
            {
                return false;
            }

            public function available(?int $producerUserId = null): array
            {
                return [];
            }

            public function findMany(array $ids): array
            {
                return [];
            }
        });

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Certification not available')
            ->assertSee('Origin details not available');
    }

    public function test_catalog_uses_a_bounded_number_of_queries_for_twelve_cards(): void
    {
        OilProduct::factory()->count(12)->create();
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        $this->get(route('catalog.index'))->assertOk();

        $this->assertLessThanOrEqual(6, $queryCount);
    }
}
