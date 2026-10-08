<?php

namespace Database\Factories\Production;

use App\Enums\MillRequestStatus;
use App\Models\Mill;
use App\Models\Production\Harvest;
use App\Models\Production\MillRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MillRequest> */
class MillRequestFactory extends Factory
{
    protected $model = MillRequest::class;

    public function definition(): array
    {
        return [
            'harvest_id' => Harvest::factory(),
            'mill_id' => Mill::factory(),
            'external_mill_name' => null,
            'requested_date' => now()->addDays(5)->toDateString(),
            'appointment_date' => null,
            'quantity_kg' => fake()->randomFloat(2, 100, 900),
            'status' => MillRequestStatus::Pending,
            'message' => 'Please schedule our Chemlali olives for this week.',
        ];
    }
}
