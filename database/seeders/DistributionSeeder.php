<?php

namespace Database\Seeders;

use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Enums\TransportType;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\Production\OilLot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class DistributionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Distribution demo data may only be seeded in local or testing environments.');
        }

        $producer = User::where('email', 'producer@test.com')->where('role', Role::Producer)->first();
        $distributor = User::where('email', 'distributor@test.com')->where('role', Role::Distributor)->first();

        if ($producer === null || $distributor === null) {
            throw new LogicException('Seed DevelopmentUserSeeder first.');
        }

        $lot1 = OilLot::where('lot_number', 'LOT-2026-001')->first();
        $lot2 = OilLot::where('lot_number', 'LOT-2026-002')->first();

        if (! $lot1 || ! $lot2) {
            throw new LogicException('Seed OilLotSeeder first to create the demo oil lots.');
        }

        DB::transaction(function () use ($producer, $distributor, $lot1, $lot2): void {
            $now = now();

            // ── Distributor profile ──────────────────────────────────────────────
            $profile = DistributorProfile::updateOrCreate(
                ['user_id' => $distributor->id],
                [
                    'company_name' => 'Zitouna Distribution SARL',
                    'address' => 'Route de Tunis Km 3, Sfax 3000, Tunisie',
                    'phone' => '+216 74 220 000',
                    'region' => 'Sfax',
                    'is_active' => true,
                ]
            );

            // ── Products — tied to real OilLot records ───────────────────────────
            //
            // Product A — LOT-001, EVOO certified, publicly visible, in catalog
            $productA = $this->upsertProduct($producer, $lot1->id, [
                'name' => 'Huile d\'olive extra vierge — El Baraka',
                'brand' => 'El Baraka',
                'bottle_volume_ml' => 750,
                'packaging_date' => '2026-10-03',
                'public_status' => OilProductPublicStatus::Visible,
                'archived_at' => null,
            ]);

            // Product B — LOT-001, 250 ml gourmet size, visible
            $productB = $this->upsertProduct($producer, $lot1->id, [
                'name' => 'Chemlali Harvest Selection — 250 ml',
                'brand' => 'Sfax Harvest',
                'bottle_volume_ml' => 250,
                'packaging_date' => '2026-10-03',
                'public_status' => OilProductPublicStatus::Visible,
                'archived_at' => null,
            ]);

            // Product C — LOT-002, premium early press, visible
            $productC = $this->upsertProduct($producer, $lot2->id, [
                'name' => 'Early Press Reserve — Domaine En Nour',
                'brand' => 'Domaine En Nour',
                'bottle_volume_ml' => 500,
                'packaging_date' => '2026-10-06',
                'public_status' => OilProductPublicStatus::Visible,
                'archived_at' => null,
            ]);

            // Product D — LOT-002, 1 L bulk catering size, visible
            $productD = $this->upsertProduct($producer, $lot2->id, [
                'name' => 'Organic Grove Blend — 1L Catering',
                'brand' => 'Zitouna Select',
                'bottle_volume_ml' => 1000,
                'packaging_date' => '2026-10-06',
                'public_status' => OilProductPublicStatus::Visible,
                'archived_at' => null,
            ]);

            // Product E — LOT-001, hidden (draft, not yet published)
            $productE = $this->upsertProduct($producer, $lot1->id, [
                'name' => 'Seasonal Trial Blend',
                'brand' => 'OliveTrace Test',
                'bottle_volume_ml' => 500,
                'packaging_date' => $now->copy()->subDays(5)->toDateString(),
                'public_status' => OilProductPublicStatus::Hidden,
                'archived_at' => null,
            ]);

            // Product F — archived (demonstrates the archive feature)
            $productF = $this->upsertProduct($producer, $lot1->id, [
                'name' => 'Old Press 2025 — Archived',
                'brand' => 'El Baraka',
                'bottle_volume_ml' => 250,
                'packaging_date' => $now->copy()->subDays(30)->toDateString(),
                'public_status' => OilProductPublicStatus::Hidden,
                'archived_at' => $now->copy()->subDays(10),
            ]);

            // ── Shipments ────────────────────────────────────────────────────────
            // 1. Product A → Tunis (delivered 2 days ago)
            $this->upsertShipment($productA, $profile, [
                'departure_location' => 'Sfax, Tunisie',
                'destination' => 'Tunis, Tunisie',
                'departure_date' => $now->copy()->subDays(4)->toDateString(),
                'arrival_date' => $now->copy()->subDays(2)->toDateString(),
                'distance_km' => '270.00',
                'quantity_bottles' => 240,
                'transport_type' => TransportType::Truck,
                'status' => ShipmentStatus::Delivered,
            ]);

            // 2. Product B → Monastir (delivered yesterday)
            $this->upsertShipment($productB, $profile, [
                'departure_location' => 'Sfax, Tunisie',
                'destination' => 'Monastir, Tunisie',
                'departure_date' => $now->copy()->subDays(3)->toDateString(),
                'arrival_date' => $now->copy()->subDay()->toDateString(),
                'distance_km' => '190.00',
                'quantity_bottles' => 80,
                'transport_type' => TransportType::Van,
                'status' => ShipmentStatus::Delivered,
            ]);

            // 3. Product C → Sousse (in transit right now)
            $this->upsertShipment($productC, $profile, [
                'departure_location' => 'Sfax, Tunisie',
                'destination' => 'Sousse, Tunisie',
                'departure_date' => $now->toDateString(),
                'arrival_date' => null,
                'distance_km' => '130.00',
                'quantity_bottles' => 120,
                'transport_type' => TransportType::Van,
                'status' => ShipmentStatus::InTransit,
            ]);

            // 4. Product D → Nabeul (planned for tomorrow)
            $this->upsertShipment($productD, $profile, [
                'departure_location' => 'Sfax, Tunisie',
                'destination' => 'Nabeul, Tunisie',
                'departure_date' => $now->copy()->addDay()->toDateString(),
                'arrival_date' => null,
                'distance_km' => '280.00',
                'quantity_bottles' => 100,
                'transport_type' => TransportType::Truck,
                'status' => ShipmentStatus::Planned,
            ]);

            // 5. Product A second shipment → Djerba (planned next week, for export demo)
            $this->upsertShipment($productA, $profile, [
                'departure_location' => 'Sfax, Tunisie',
                'destination' => 'Djerba — Export Port',
                'departure_date' => $now->copy()->addDays(5)->toDateString(),
                'arrival_date' => null,
                'distance_km' => '150.00',
                'quantity_bottles' => 50,
                'transport_type' => TransportType::Truck,
                'status' => ShipmentStatus::Planned,
            ]);
        });
    }

    private function upsertProduct(User $producer, int $lotId, array $attrs): OilProduct
    {
        $product = OilProduct::query()
            ->where('created_by_user_id', $producer->id)
            ->where('name', $attrs['name'])
            ->first();

        if (! $product) {
            $product = new OilProduct;
            $product->created_by_user_id = $producer->id;
            $product->oil_lot_id = $lotId;
            $product->name = $attrs['name'];
        }

        $product->oil_lot_id = $lotId;
        $product->fill([
            'brand' => $attrs['brand'],
            'bottle_volume_ml' => $attrs['bottle_volume_ml'],
            'packaging_date' => $attrs['packaging_date'],
            'public_status' => $attrs['public_status'],
        ]);
        $product->archived_at = $attrs['archived_at'] ?? null;
        $product->save();

        return $product;
    }

    private function upsertShipment(OilProduct $product, DistributorProfile $profile, array $attrs): Shipment
    {
        return Shipment::updateOrCreate(
            [
                'oil_product_id' => $product->id,
                'distributor_profile_id' => $profile->id,
                'departure_location' => $attrs['departure_location'],
                'destination' => $attrs['destination'],
            ],
            [
                'departure_date' => $attrs['departure_date'],
                'arrival_date' => $attrs['arrival_date'],
                'distance_km' => $attrs['distance_km'],
                'quantity_bottles' => $attrs['quantity_bottles'],
                'transport_type' => $attrs['transport_type'],
                'status' => $attrs['status'],
            ]
        );
    }
}
