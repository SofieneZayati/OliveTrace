<?php

namespace Tests\Feature;

use App\Contracts\OilLotLookup;
use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DistributionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_distribution_models_expose_eloquent_relations_and_temporary_lot_lookup(): void
    {
        $product = OilProduct::factory()->create();
        $profile = DistributorProfile::factory()->create();
        $shipment = Shipment::factory()->for($product)->for($profile)->create();

        $this->assertTrue($product->createdBy->hasRole(Role::Producer));
        $this->assertSame($product->id, $shipment->oilProduct->id);
        $this->assertSame($profile->id, $shipment->distributorProfile->id);
        $this->assertTrue($profile->user->hasRole(Role::Distributor));
        $this->assertCount(1, $product->shipments);
        $this->assertCount(1, $profile->shipments);
        $this->assertNotNull($product->oilLot());
        $this->assertStringStartsWith('TEST-LOT-', $product->oilLot()->lotCode);
    }

    public function test_product_scopes_filter_visibility_status_date_and_owner(): void
    {
        $owner = User::factory()->state(['role' => Role::Producer])->create();
        $visible = OilProduct::factory()->create([
            'created_by_user_id' => $owner->id,
            'packaging_date' => '2026-10-01',
        ]);
        OilProduct::factory()->hidden()->create([
            'created_by_user_id' => $owner->id,
            'packaging_date' => '2026-10-02',
        ]);
        OilProduct::factory()->archived()->create([
            'created_by_user_id' => $owner->id,
            'packaging_date' => '2026-10-03',
        ]);

        $this->assertSame([$visible->id], OilProduct::publiclyVisible()->ownedBy($owner)->pluck('id')->all());
        $this->assertSame(
            [$visible->id],
            OilProduct::query()->byStatus(OilProductPublicStatus::Visible)->packagingDateBetween('2026-10-01', '2026-10-01')->pluck('id')->all()
        );
        $this->assertSame($visible->id, OilProduct::findBySlug((string) $visible->slug)?->id);
    }

    public function test_total_co2_sums_non_cancelled_shipments_only(): void
    {
        $product = OilProduct::factory()->create();
        Shipment::factory()->for($product)->create(['co2_estimate' => '12.25']);
        Shipment::factory()->for($product)->delivered()->create(['co2_estimate' => '7.75']);
        Shipment::factory()->for($product)->create([
            'status' => ShipmentStatus::Cancelled,
            'co2_estimate' => '100.00',
        ]);
        Shipment::factory()->for($product)->create(['co2_estimate' => null]);

        $this->assertSame(20.0, $product->totalCo2Kg());
    }

    public function test_archive_hides_product_without_deleting_it(): void
    {
        $product = OilProduct::factory()->create();

        $this->assertTrue($product->archive());
        $this->assertNotNull($product->fresh()->archived_at);
        $this->assertSame(OilProductPublicStatus::Hidden, $product->fresh()->public_status);
        $this->assertFalse($product->fresh()->isPubliclyVisible());
        $this->assertDatabaseHas('oil_products', ['id' => $product->id]);
    }

    public function test_temporary_oil_lot_lookup_reports_missing_and_existing_lots(): void
    {
        $id = DB::table('oil_lots')->insertGetId([
            'harvest_id' => null,
            'lot_code' => 'LOT-SFAX-TEST',
            'extraction_date' => '2026-10-01',
            'volume_l' => '150.00',
            'grade' => 'Extra virgin',
            'acidity' => '0.250',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lookup = app(OilLotLookup::class);

        $this->assertTrue($lookup->exists($id));
        $this->assertSame('LOT-SFAX-TEST', $lookup->find($id)?->lotCode);
        $this->assertFalse($lookup->exists($id + 1000));
        $this->assertNull($lookup->find($id + 1000));
        $this->assertContains($id, collect($lookup->available())->pluck('id')->all());

        OilProduct::factory()->create(['oil_lot_id' => $id]);
        $this->assertNotContains($id, collect($lookup->available())->pluck('id')->all());
    }
}
