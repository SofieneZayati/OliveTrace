<?php

namespace Database\Seeders;

use App\Enums\OilQuality;
use App\Enums\Role;
use App\Models\Production\MillRequest;
use App\Models\Production\OilLot;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class OilLotSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('OilLot demo data may only be seeded in local or testing environments.');
        }

        $producer = User::where('email', 'producer@test.com')->where('role', Role::Producer)->first();
        if (! $producer) {
            throw new LogicException('Seed DevelopmentUserSeeder first.');
        }

        // Find the completed mill request (the Parcel En Nour harvest that was milled)
        $completedRequest = MillRequest::where('status', 'completed')->first();

        if ($completedRequest && ! $completedRequest->oilLot) {
            OilLot::firstOrCreate(
                ['lot_number' => 'LOT-2026-001'],
                [
                    'mill_request_id'  => $completedRequest->id,
                    'producer_user_id' => $producer->id,
                    'liters'           => '214.00',
                    'quality_grade'    => OilQuality::ExtraVirgin,
                    'production_date'  => '2026-09-28',
                    'notes'            => 'First press of the Parcel En Nour trunk-shaker harvest. Chemlali variety, low acidity, fruity finish.',
                ]
            );
        }

        // A second lot: the early hand-picked batch (mill request still pending, so we add it manually with a note)
        OilLot::firstOrCreate(
            ['lot_number' => 'LOT-2026-002'],
            [
                'mill_request_id'  => null, // pending at external mill
                'producer_user_id' => $producer->id,
                'liters'           => '118.00',
                'quality_grade'    => OilQuality::ExtraVirgin,
                'production_date'  => '2026-10-05',
                'notes'            => 'Early hand-picked batch from Farm El Baraka, pressed at Huilerie El Molla Menzel. Premium selection, acidity 0.18%.',
            ]
        );
    }
}
