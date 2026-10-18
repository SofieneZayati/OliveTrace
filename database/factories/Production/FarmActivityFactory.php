<?php

namespace Database\Factories\Production;

use App\Enums\FarmActivityType;
use App\Models\Production\Farm;
use App\Models\Production\FarmActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FarmActivity> */
class FarmActivityFactory extends Factory
{
    protected $model = FarmActivity::class;

    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(), 'title' => 'Inspect the olive parcel',
            'type' => FarmActivityType::Inspection, 'planned_on' => today()->toDateString(),
            'status' => 'planned', 'notes' => null,
        ];
    }
}
