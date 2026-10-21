<?php

namespace Database\Factories\Consumer;

use App\Models\Consumer\AdminSuggestedResponse;
use App\Models\Consumer\Feedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminSuggestedResponse>
 */
class AdminSuggestedResponseFactory extends Factory
{
    protected $model = AdminSuggestedResponse::class;

    public function definition(): array
    {
        return [
            'feedback_id' => Feedback::factory(),
            'suggested_response' => fake()->paragraph(2),
            'tone' => fake()->randomElement(['professional', 'empathetic', 'formal', 'friendly']),
            'confidence' => fake()->randomFloat(2, 0.5, 1.0),
            'used' => false,
            'used_by' => null,
            'used_at' => null,
        ];
    }
}
