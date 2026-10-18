<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class FarmJournalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Farm journal demo data is only available in local or testing environments.');
        }
        $owner = User::where('email', 'producer@test.com')->where('role', Role::Producer)->first();
        $farm = $owner?->producerProfile?->farms()->where('name', 'Farm El Baraka')->first();
        if (! $farm instanceof Farm) {
            throw new LogicException('Seed DevelopmentUserSeeder and ProductionSeeder first.');
        }
        foreach ([
            ['title' => 'Demo: review soil cover', 'type' => 'inspection', 'planned_on' => today()->subDays(2)->toDateString()],
            ['title' => 'Demo: plan pruning review', 'type' => 'pruning', 'planned_on' => today()->addDays(7)->toDateString()],
        ] as $data) {
            // Never overwrite edits or reset the status of an existing demonstration entry.
            $farm->activities()->firstOrCreate(['title' => $data['title']], $data + ['notes' => 'Development demonstration only. Record completion after reviewing the task.']);
        }
        // AI reports are deliberately not seeded. Generate genuine reports through the configured provider.
    }
}
