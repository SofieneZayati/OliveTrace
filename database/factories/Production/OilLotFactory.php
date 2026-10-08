<?php

namespace Database\Factories\Production;

use App\Enums\OilQuality;
use App\Models\Production\MillRequest;
use App\Models\Production\OilLot;
use Illuminate\Database\Eloquent\Factories\Factory;

class OilLotFactory extends Factory
{
    protected $model = OilLot::class;

    public function definition(): array
    {
        return [
            'mill_request_id' => MillRequest::factory()->state(fn () => ['status' => 'completed']),
            'lot_number' => 'LOT-'.fake()->year().'-'.fake()->unique()->numberBetween(100, 999),
            'liters' => fake()->randomFloat(2, 50, 500),
            'quality_grade' => fake()->randomElement(OilQuality::cases()),
            'production_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
