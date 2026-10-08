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
        $this->call([
            DevelopmentUserSeeder::class,
            ProductionSeeder::class,
            MillSeeder::class,
            HarvestSeeder::class,
            MillRequestSeeder::class,
            OilLotSeeder::class,
            CertificationSeeder::class,
            DistributionSeeder::class,
            ConsumerSeeder::class,
        ]);
    }
}
