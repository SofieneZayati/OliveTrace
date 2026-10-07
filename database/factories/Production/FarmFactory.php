<?php

namespace Database\Factories\Production;

use App\Enums\FarmingType;
use App\Enums\FarmStatus;
use App\Enums\IrrigationType;
use App\Models\Production\Farm;
use App\Models\Production\ProducerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Farm> */
class FarmFactory extends Factory
{
    protected $model = Farm::class;

    public function definition(): array
    {
        return [
            'producer_profile_id' => ProducerProfile::factory(),
            'name' => 'Farm '.fake()->unique()->word(), 'governorate' => 'Sfax',
            'delegation' => 'Agareb', 'area_ha' => fake()->randomFloat(2, 1, 35),
            'olive_variety' => 'Chemlali', 'farming_type' => FarmingType::Integrated,
            'irrigation_type' => IrrigationType::Rainfed,
            'status' => FarmStatus::Active, 'is_public' => false,
        ];
    }
}
