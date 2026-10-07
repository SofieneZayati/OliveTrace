<?php

namespace Tests\Feature;

use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Enums\TransportType;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\User;
use Database\Seeders\DistributionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DistributionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_producer_can_create_and_edit_own_product_but_cannot_edit_another_producers_product(): void
    {
        $producer = User::factory()->state(['role' => Role::Producer])->create();
        $anotherProducer = User::factory()->state(['role' => Role::Producer])->create();
        $this->actingAs($producer);
        $lotId = $this->createLot('LOT-CREATE-001');

        $this->get(route('producer.products.create'))->assertOk()->assertSee('LOT-CREATE-001');
        $this->post(route('producer.products.store'), $this->validProduct($lotId))->assertRedirect();
        $created = OilProduct::query()->where('created_by_user_id', $producer->id)->firstOrFail();
        $this->assertNotEmpty($created->slug);
        $this->assertDatabaseHas('oil_products', ['id' => $created->id, 'created_by_user_id' => $producer->id]);
        $this->get(route('producer.products.show', $created))->assertOk()->assertSee('<svg', false);

        $ownLotId = $this->createLot('LOT-UPDATE-001');
        $this->patch(route('producer.products.update', $created), $this->validProduct($ownLotId, ['name' => 'Updated Chemlali']))
            ->assertRedirect(route('producer.products.show', $created));
        $this->assertSame('Updated Chemlali', $created->fresh()->name);

        $otherProduct = OilProduct::factory()->create(['created_by_user_id' => $anotherProducer->id]);
        $this->patch(route('producer.products.update', $otherProduct), $this->validProduct((int) $otherProduct->oil_lot_id))
            ->assertForbidden();
    }

    public function test_distributor_can_update_only_shipments_belonging_to_their_profile(): void
    {
        $distributorA = DistributorProfile::factory()->create();
        $distributorB = DistributorProfile::factory()->create();
        $shipmentA = Shipment::factory()->for($distributorA)->create();
        $shipmentB = Shipment::factory()->for($distributorB)->create();

        $this->actingAs($distributorA->user)
            ->get(route('distributor.shipments.edit', $shipmentA))
            ->assertOk();
        $this->patch(route('distributor.shipments.update', $shipmentA), $this->validShipment($shipmentA))
            ->assertRedirect(route('distributor.shipments.show', $shipmentA));
        $this->patch(route('distributor.shipments.update', $shipmentB), $this->validShipment($shipmentB))
            ->assertForbidden();
    }

    public function test_consumer_is_forbidden_from_product_shipment_and_admin_spaces(): void
    {
        $consumer = User::factory()->state(['role' => Role::Consumer])->create();

        $this->actingAs($consumer)->get(route('producer.products.index'))->assertForbidden();
        $this->get(route('distributor.shipments.index'))->assertForbidden();
        $this->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_admin_can_list_products_and_hide_then_unhide_a_public_page(): void
    {
        $admin = User::factory()->state(['role' => Role::Admin])->create();
        $product = OilProduct::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee($product->name);

        $this->patch(route('admin.products.visibility', $product), ['public_status' => 'hidden'])->assertRedirect();
        $this->assertSame(OilProductPublicStatus::Hidden, $product->fresh()->public_status);
        $this->patch(route('admin.products.visibility', $product), ['public_status' => 'visible'])->assertRedirect();
        $this->assertSame(OilProductPublicStatus::Visible, $product->fresh()->public_status);
    }

    public function test_guest_is_redirected_to_login_for_distribution_routes(): void
    {
        $this->get(route('producer.products.index'))->assertRedirect(route('login'));
        $this->get(route('distributor.shipments.index'))->assertRedirect(route('login'));
    }

    public function test_shipment_status_route_accepts_valid_transition_and_rejects_invalid_transition(): void
    {
        $profile = DistributorProfile::factory()->create();
        $shipment = Shipment::factory()->for($profile)->create(['status' => ShipmentStatus::Planned]);
        $this->actingAs($profile->user);

        $this->patch(route('distributor.shipments.status', $shipment), ['status' => ShipmentStatus::InTransit->value])->assertRedirect();
        $this->assertSame(ShipmentStatus::InTransit, $shipment->fresh()->status);

        $this->patch(route('distributor.shipments.status', $shipment), ['status' => ShipmentStatus::Planned->value])
            ->assertSessionHasErrors('status');
        $this->assertSame(ShipmentStatus::InTransit, $shipment->fresh()->status);
    }

    public function test_profile_can_be_created_and_updated_by_its_distributor(): void
    {
        $user = User::factory()->state(['role' => Role::Distributor])->create();
        $this->actingAs($user);
        $profilePayload = [
            'company_name' => 'Sfax Olive Logistics',
            'address' => 'Route de Tunis, Sfax',
            'phone' => '+216 74 000 000',
            'region' => 'Sfax',
        ];

        $this->post(route('distributor.profile.store'), $profilePayload)->assertRedirect(route('distributor.profile.show'));
        $this->assertDatabaseHas('distributor_profiles', ['user_id' => $user->id, 'company_name' => 'Sfax Olive Logistics']);
        $this->patch(route('distributor.profile.update'), array_merge($profilePayload, ['company_name' => 'Sfax Olive Logistics Updated']))
            ->assertRedirect(route('distributor.profile.show'));
        $this->assertSame('Sfax Olive Logistics Updated', DistributorProfile::query()->where('user_id', $user->id)->firstOrFail()->company_name);
    }

    public function test_distribution_seeder_is_re_runnable_and_does_not_change_demo_users(): void
    {
        $producer = User::factory()->state(['role' => Role::Producer])->create(['email' => 'producer@test.com']);
        $distributor = User::factory()->state(['role' => Role::Distributor])->create(['email' => 'distributor@test.com']);
        $producerPassword = $producer->password;
        $distributorPassword = $distributor->password;

        $this->seed(DistributionSeeder::class);
        $this->seed(DistributionSeeder::class);

        $this->assertSame($producerPassword, $producer->fresh()->password);
        $this->assertSame($distributorPassword, $distributor->fresh()->password);
        $this->assertSame(1, DB::table('oil_lots')->where('lot_code', 'LOT-2026-001')->count());
        $this->assertSame(1, DistributorProfile::query()->where('user_id', $distributor->id)->count());
        $this->assertSame(1, OilProduct::query()->where('created_by_user_id', $producer->id)->where('name', 'Huile d’olive vierge extra - El Baraka')->count());
        $this->assertSame(2, Shipment::query()->count());
    }

    private function createLot(string $lotCode): int
    {
        return DB::table('oil_lots')->insertGetId([
            'harvest_id' => null,
            'lot_code' => $lotCode,
            'extraction_date' => now()->subDay()->toDateString(),
            'volume_l' => '200.00',
            'grade' => 'Extra virgin - Chemlali',
            'acidity' => '0.300',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function validProduct(int $lotId, array $overrides = []): array
    {
        return array_merge([
            'oil_lot_id' => $lotId,
            'name' => 'Extra virgin Chemlali',
            'brand' => 'El Baraka',
            'bottle_volume_ml' => 750,
            'packaging_date' => now()->subDay()->toDateString(),
            'public_status' => OilProductPublicStatus::Visible->value,
            'slug' => 'forged-user-slug',
        ], $overrides);
    }

    private function validShipment(Shipment $shipment): array
    {
        return [
            'oil_product_id' => $shipment->oil_product_id,
            'departure_location' => 'Sfax, Tunisia',
            'destination' => 'Tunis, Tunisia',
            'departure_date' => now()->toDateString(),
            'arrival_date' => null,
            'distance_km' => '270.00',
            'transport_type' => TransportType::Truck->value,
            'status' => $shipment->status->value,
            'co2_estimate' => '999.00',
        ];
    }
}
