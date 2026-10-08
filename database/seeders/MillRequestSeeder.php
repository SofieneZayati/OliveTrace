<?php

namespace Database\Seeders;

use App\Enums\MillRequestStatus;
use App\Models\Mill;
use App\Models\Production\Harvest;
use App\Models\Production\MillRequest;
use Illuminate\Database\Seeder;
use LogicException;

class MillRequestSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Production demo data may only be seeded in local or testing environments.');
        }
        $mills = Mill::orderBy('id')->get();
        if ($mills->isEmpty()) {
            throw new LogicException('Seed MillSeeder first.');
        }

        $demo = [
            '2026-10-01' => [
                'mill_id' => $mills[0]->id, 'external_mill_name' => null,
                'requested_date' => '2026-10-10', 'appointment_date' => '2026-10-14',
                'quantity_kg' => 1250, 'status' => MillRequestStatus::Accepted,
                'message' => 'Main Chemlali parcel, harvest still running. Can you schedule us?',
                'response_message' => 'Slot confirmed for October 14, bring the trailer before 8am.',
            ],
            '2026-09-20' => [
                'mill_id' => null, 'external_mill_name' => 'Huilerie El Molla Menzel',
                'requested_date' => '2026-10-16', 'appointment_date' => null,
                'quantity_kg' => 500, 'status' => MillRequestStatus::Pending,
                'message' => 'External mill for the early hand picked batch.',
                'response_message' => null,
            ],
            '2026-09-15' => [
                'mill_id' => $mills[0]->id, 'external_mill_name' => null,
                'requested_date' => '2026-09-25', 'appointment_date' => '2026-09-28',
                'quantity_kg' => 860, 'status' => MillRequestStatus::Completed,
                'message' => null,
                'response_message' => 'Milled into LOT-2026-001: 214 L of extra virgin oil.',
            ],
        ];

        foreach ($demo as $harvestDate => $data) {
            $harvest = Harvest::where('harvest_date', $harvestDate)->first();
            if (! $harvest) {
                continue;
            }
            MillRequest::updateOrCreate(['harvest_id' => $harvest->id], $data);
        }
    }
}
