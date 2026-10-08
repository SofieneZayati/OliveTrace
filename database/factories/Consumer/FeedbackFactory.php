<?php

namespace Database\Factories\Consumer;

use App\Enums\Consumer\FeedbackStatus;
use App\Models\Consumer\Feedback;
use App\Models\Distribution\OilProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition(): array
    {
        return [
            'oil_product_id' => OilProduct::factory(),
            'consumer_user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(12),
            'status' => FeedbackStatus::Visible,
        ];
    }
}
