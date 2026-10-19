<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\FarmActivity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class FarmCareDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Farm care demo data is only available in local or testing environments.');
        }
        $owner = User::where('email', 'producer@test.com')->where('role', Role::Producer)->first();
        $profile = $owner?->producerProfile;
        if (! $profile) {
            throw new LogicException('Create the development producer and profile before running FarmCareDemoSeeder.');
        }

        DB::transaction(function () use ($profile) {
            foreach ([
                ['name' => '[Demo] Sfax Coastal Grove', 'governorate' => 'Sfax', 'area_ha' => '14.50', 'olive_variety' => 'Chemlali', 'irrigation_type' => 'drip', 'gps_lat' => '34.74', 'gps_lng' => '10.76', 'status' => 'active'],
                ['name' => '[Demo] Zaghouan Hillside', 'governorate' => 'Zaghouan', 'area_ha' => '8.25', 'olive_variety' => 'Chetoui', 'irrigation_type' => 'rainfed', 'gps_lat' => '36.40', 'gps_lng' => '10.15', 'status' => 'active'],
                ['name' => '[Demo] Beja North Plot', 'governorate' => 'Beja', 'area_ha' => '6.75', 'olive_variety' => 'Chetoui', 'irrigation_type' => 'rainfed', 'gps_lat' => '36.73', 'gps_lng' => '9.18', 'status' => 'archived'],
                ['name' => '[Demo] Kairouan Dryland', 'governorate' => 'Kairouan', 'area_ha' => '11.00', 'olive_variety' => 'Oueslati', 'irrigation_type' => 'rainfed', 'gps_lat' => '35.67', 'gps_lng' => '10.10', 'status' => 'disabled'],
            ] as $data) {
                $farm = $profile->farms()->where('name', $data['name'])->first();
                if (! $farm) {
                    $farm = new Farm;
                    $farm->forceFill($data + [
                        'producer_profile_id' => $profile->id, 'delegation' => null,
                        'farming_type' => 'integrated', 'is_public' => false,
                        'description' => 'DEVELOPMENT DEMO ONLY. Fictional farm with an illustrative regional map point. No real farm boundary, ownership or environmental result is asserted.',
                    ])->save();
                }

                // Name/title matches are insert-only: retain edited coordinates, notes and states.
                foreach ($this->activities($data['irrigation_type'] === 'drip', $data['status'] === 'active') as $activity) {
                    if ($farm->activities()->where('title', $activity['title'])->exists()) {
                        continue;
                    }
                    $entry = new FarmActivity;
                    $entry->forceFill($activity + ['farm_id' => $farm->id, 'notes' => 'DEVELOPMENT DEMO ONLY. Fictional farm-care record for testing the interface and AI input.']);
                    $entry->save();
                }
            }
        });
        // Reports must come from an actual provider request, never fabricated AI seed output.
    }

    private function activities(bool $irrigated, bool $active): array
    {
        $history = [
            ['title' => 'Demo: completed field inspection', 'type' => 'inspection', 'planned_on' => today()->subDays(3)->toDateString(), 'completed_on' => today()->subDays(3)->toDateString(), 'status' => 'completed'],
            ['title' => 'Demo: overdue follow-up inspection', 'type' => 'inspection', 'planned_on' => today()->subDays(2)->toDateString(), 'status' => 'planned'],
        ];
        if (! $active) {
            return $history;
        }

        return array_merge($history, [
            ['title' => $irrigated ? 'Demo: completed irrigation round' : 'Demo: completed soil-cover review', 'type' => $irrigated ? 'irrigation' : 'soil_care', 'planned_on' => today()->subDays(6)->toDateString(), 'completed_on' => today()->subDays(5)->toDateString(), 'water_m3' => $irrigated ? '24.50' : null, 'status' => 'completed'],
            ['title' => 'Demo: upcoming soil-care review', 'type' => 'soil_care', 'planned_on' => today()->addDays(3)->toDateString(), 'status' => 'planned'],
            ['title' => 'Demo: upcoming pruning review', 'type' => 'pruning', 'planned_on' => today()->addDays(10)->toDateString(), 'status' => 'planned'],
            ['title' => 'Demo: cancelled optional inspection', 'type' => 'inspection', 'planned_on' => today()->subDay()->toDateString(), 'status' => 'cancelled'],
        ]);
    }
}
