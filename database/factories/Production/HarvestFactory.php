<?php

namespace Database\Factories\Production;

use App\Enums\HarvestMethod;
use App\Enums\HarvestStatus;
use App\Models\Production\Farm;
use App\Models\Production\Harvest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Harvest> */
class HarvestFactory extends Factory
{
    protected $model = Harvest::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-35 days', '-4 days');

        return [
            'farm_id' => Farm::factory(),
            'harvest_date' => $start,
            'expected_end_date' => (clone $start)->modify('+'.fake()->numberBetween(4, 21).' days')->format('Y-m-d'),
            'method' => fake()->randomElement(HarvestMethod::cases()),
            'quantity_kg' => fake()->boolean(80) ? fake()->randomFloat(2, 150, 3500) : null,
            'status' => fake()->randomElement([HarvestStatus::Declared, HarvestStatus::InProgress, HarvestStatus::Completed]),
            'notes' => 'Chemlali olives harvested with care for the OliveTrace story.',
        ];
    }
}
