<?php

namespace Database\Seeders;

use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Enums\TransportType;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
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

        $producer = User::query()->where('email', 'producer@test.com')->where('role', Role::Producer)->first();
        $distributor = User::query()->where('email', 'distributor@test.com')->where('role', Role::Distributor)->first();
        if ($producer === null || $distributor === null) {
            throw new LogicException('Seed DevelopmentUserSeeder first to create the demo producer and distributor.');
        }

        DB::transaction(function () use ($producer, $distributor): void {
            $now = now();
            DB::table('oil_lots')->updateOrInsert(
                ['lot_code' => 'LOT-2026-001'],
                [
                    'harvest_id' => null,
                    'extraction_date' => $now->copy()->subDays(2)->toDateString(),
                    'volume_l' => '750.00',
                    'grade' => 'Extra virgin - Chemlali',
                    'acidity' => '0.300',
                    'notes' => 'Demo lot from the Sfax region.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
            $lot = DB::table('oil_lots')->where('lot_code', 'LOT-2026-001')->firstOrFail();

            $profile = DistributorProfile::query()->updateOrCreate(
                ['user_id' => $distributor->id],
                [
                    'company_name' => 'OliveTrace Distribution',
                    'address' => 'Route de Tunis, Sfax',
                    'phone' => '+216 74 000 000',
                    'region' => 'Sfax',
                    'is_active' => true,
                ],
            );
            $product = OilProduct::query()->firstOrNew([
                'oil_lot_id' => (int) $lot->id,
                'created_by_user_id' => $producer->id,
                'name' => 'Huile d’olive vierge extra - El Baraka',
            ]);
            $product->oil_lot_id = (int) $lot->id;
            $product->created_by_user_id = $producer->id;
            $product->fill([
                'brand' => 'El Baraka',
                'bottle_volume_ml' => 750,
                'packaging_date' => $now->copy()->subDay()->toDateString(),
                'public_status' => OilProductPublicStatus::Visible,
            ]);
            $product->archived_at = null;
            $product->save();
            $catalogProducts = [$product];

            foreach ([
                ['name' => 'Chemlali Harvest Selection', 'brand' => 'Sfax Harvest', 'volume' => 250, 'status' => OilProductPublicStatus::Visible],
                ['name' => 'Organic Grove Blend', 'brand' => 'Domaine En Nour', 'volume' => 500, 'status' => OilProductPublicStatus::Visible],
                ['name' => 'Early Press Reserve', 'brand' => 'Zitouna Select', 'volume' => 1000, 'status' => OilProductPublicStatus::Visible],
                ['name' => 'Seasonal Trial Blend', 'brand' => 'OliveTrace Test', 'volume' => 500, 'status' => OilProductPublicStatus::Hidden],
                ['name' => 'Archived Grove Oil', 'brand' => 'Old Press', 'volume' => 250, 'status' => OilProductPublicStatus::Hidden, 'archived' => true],
            ] as $sample) {
                $sampleProduct = OilProduct::query()->firstOrNew([
                    'created_by_user_id' => $producer->id,
                    'name' => $sample['name'],
                ]);
                $sampleProduct->created_by_user_id = $producer->id;
                $sampleProduct->oil_lot_id = (int) $lot->id;
                $sampleProduct->fill([
                    'name' => $sample['name'],
                    'brand' => $sample['brand'],
                    'bottle_volume_ml' => $sample['volume'],
                    'packaging_date' => $now->copy()->subDays(2)->toDateString(),
                    'public_status' => $sample['status'],
                ]);
                if ($sample['archived'] ?? false) {
                    $sampleProduct->archived_at = $now->copy()->subDay();
                } else {
                    $sampleProduct->archived_at = null;
                }
                $sampleProduct->save();

                if ($sample['status'] === OilProductPublicStatus::Visible) {
                    $catalogProducts[] = $sampleProduct;
                }
            }

            Shipment::query()->updateOrCreate(
                [
                    'oil_product_id' => $product->id,
                    'distributor_profile_id' => $profile->id,
                    'departure_location' => 'Sfax, Tunisia',
                    'destination' => 'Tunis, Tunisia',
                    'status' => ShipmentStatus::Delivered,
                ],
                [
                    'departure_date' => $now->copy()->subDays(2)->toDateString(),
                    'arrival_date' => $now->copy()->subDay()->toDateString(),
                    'distance_km' => '270.00',
                    'transport_type' => TransportType::Truck,
                    'co2_estimate' => null,
                ],
            );
            Shipment::query()->updateOrCreate(
                [
                    'oil_product_id' => $catalogProducts[1]->id,
                    'distributor_profile_id' => $profile->id,
                    'departure_location' => 'Sfax, Tunisia',
                    'destination' => 'Monastir, Tunisia',
                    'status' => ShipmentStatus::Delivered,
                ],
                [
                    'departure_date' => $now->copy()->subDays(3)->toDateString(),
                    'arrival_date' => $now->copy()->subDays(2)->toDateString(),
                    'distance_km' => '190.00',
                    'transport_type' => TransportType::Truck,
                    'co2_estimate' => null,
                ],
            );
            Shipment::query()->updateOrCreate(
                [
                    'oil_product_id' => $product->id,
                    'distributor_profile_id' => $profile->id,
                    'departure_location' => 'Sfax, Tunisia',
                    'destination' => 'Sousse, Tunisia',
                    'status' => ShipmentStatus::Planned,
                ],
                [
                    'departure_date' => $now->copy()->addDay()->toDateString(),
                    'arrival_date' => null,
                    'distance_km' => '130.00',
                    'transport_type' => TransportType::Van,
                    'co2_estimate' => null,
                ],
            );
        });
    }
}
