<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Production demo data may only be seeded in local or testing environments.');
        }
        $user = User::where('email', 'producer@test.com')->where('role', Role::Producer)->first();
        if (! $user) {
            throw new LogicException('Seed DevelopmentUserSeeder first to create the demo producer.');
        }

        DB::transaction(function () use ($user) {
            $profile = $user->producerProfile;
            if (! $profile) {
                $profile = ProducerProfile::factory()->for($user)->create([
                    'display_name' => 'El Baraka Olive Growers', 'company_name' => 'El Baraka',
                    'address' => 'Agareb, Sfax (demo)', 'is_public' => true,
                ]);
            }
            foreach (['Farm El Baraka' => '12.50', 'Parcel En Nour' => '6.25'] as $name => $area) {
                if ($profile->farms()->where('name', $name)->exists()) {
                    continue;
                }
                Farm::factory()->for($profile, 'producerProfile')->create([
                    'name' => $name, 'area_ha' => $area, 'is_public' => true,
                    'description' => 'Demo olive parcel in Sfax, ready for Mariem’s harvest workflow.',
                ]);
            }
        });
    }
}
