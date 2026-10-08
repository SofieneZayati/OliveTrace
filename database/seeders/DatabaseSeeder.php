<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([DevelopmentUserSeeder::class, ProductionSeeder::class]);
        $this->call(MillSeeder::class);
        $this->call([HarvestSeeder::class, MillRequestSeeder::class]);
        $this->call(OilLotSeeder::class);
        $this->call(CertificationSeeder::class);
    }
}
