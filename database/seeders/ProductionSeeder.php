<?php

namespace Database\Seeders;

use App\Entities\Production\Farm;
use App\Enums\Role;
use App\Models\User;
use App\Repositories\Production\ProducerProfiles;
use Database\Factories\Production\FarmFactory;
use Database\Factories\Production\ProducerProfileFactory;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Database\Seeder;
use LogicException;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Production demo data may only be seeded in local or testing environments.');
        }
        $user = User::where('email', 'producer@test.com')->where('role', Role::Producer->value)->first();
        if (! $user) {
            throw new LogicException('Seed DevelopmentUserSeeder first to create the demo producer.');
        }
        $manager = app(EntityManagerInterface::class);
        $profile = app(ProducerProfiles::class)->forUser($user->id);
        if (! $profile) {
            $profile = (new ProducerProfileFactory)->make($user->id, [
                'displayName' => 'El Baraka Olive Growers', 'companyName' => 'El Baraka',
                'address' => 'Agareb, Sfax (demo)', 'isPublic' => true,
            ]);
            $manager->persist($profile);
            $manager->flush();
        }
        foreach (['Farm El Baraka' => '12.50', 'Parcel En Nour' => '6.25'] as $name => $area) {
            if ($manager->getRepository(Farm::class)->findOneBy(['producerProfile' => $profile, 'name' => $name])) {
                continue;
            }
            $farm = (new FarmFactory)->make($profile, [
                'name' => $name, 'areaHa' => $area, 'isPublic' => true,
                'description' => 'Demo olive parcel in Sfax, ready for Mariem’s harvest workflow.',
            ]);
            $manager->persist($farm);
        }
        $manager->flush();
    }
}
