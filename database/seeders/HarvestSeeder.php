<?php

namespace Database\Seeders;

use App\Enums\HarvestMethod;
use App\Enums\HarvestStatus;
use App\Enums\Role;
use App\Models\Production\Harvest;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class HarvestSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Production demo data may only be seeded in local or testing environments.');
        }
        $user = User::where('email', 'producer@test.com')->where('role', Role::Producer)->first();
        if (! $user || ! $user->producerProfile) {
            throw new LogicException('Seed DevelopmentUserSeeder and ProductionSeeder first.');
        }

        $baraka = $user->producerProfile->farms()->where('name', 'Farm El Baraka')->first();
        $nour = $user->producerProfile->farms()->where('name', 'Parcel En Nour')->first();
        if (! $baraka || ! $nour) {
            throw new LogicException('Seed ProductionSeeder first to create the demo farms.');
        }

        $demo = [
            [$baraka, '2026-10-01', '2026-10-12', HarvestMethod::PneumaticComb, 1250, HarvestStatus::InProgress, 'Chemlali olives of the main parcel. The harvest is still running.'],
            [$baraka, '2026-09-20', '2026-09-27', HarvestMethod::HandPicking, 500, HarvestStatus::Completed, 'Early hand picked batch reserved for the premium lot.'],
            [$nour, '2026-09-15', '2026-09-22', HarvestMethod::TrunkShaker, 860, HarvestStatus::Milled, 'Already turned into oil by the demo mill.'],
        ];

        foreach ($demo as [$farm, $date, $expectedEnd, $method, $quantity, $status, $notes]) {
            $harvest = Harvest::withTrashed()->updateOrCreate(
                ['farm_id' => $farm->id, 'harvest_date' => $date],
                ['expected_end_date' => $expectedEnd, 'method' => $method, 'quantity_kg' => $quantity, 'status' => $status, 'notes' => $notes]
            );
            if ($harvest->trashed()) {
                $harvest->restore();
            }
        }
    }
}
