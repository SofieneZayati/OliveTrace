<?php

namespace Database\Factories\Production;

use App\Entities\Production\Farm;
use App\Entities\Production\ProducerProfile;
use App\Enums\FarmingType;
use App\Enums\IrrigationType;
use Faker\Factory;

class FarmFactory
{
    public function make(ProducerProfile $profile, array $overrides = []): Farm
    {
        $faker = Factory::create();
        $farm = new Farm($profile);
        $farm->name = 'Farm '.$faker->unique()->word();
        $farm->governorate = 'Sfax';
        $farm->delegation = 'Agareb';
        $farm->areaHa = number_format($faker->randomFloat(2, 1, 35), 2, '.', '');
        $farm->oliveVariety = 'Chemlali';
        $farm->farmingType = FarmingType::Integrated;
        $farm->irrigationType = IrrigationType::Rainfed;
        foreach ($overrides as $property => $value) {
            $farm->{$property} = $value;
        }

        return $farm;
    }
}
