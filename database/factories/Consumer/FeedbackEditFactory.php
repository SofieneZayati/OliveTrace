<?php

namespace Database\Factories\Consumer;

use App\Models\Consumer\Feedback;
use App\Models\Consumer\FeedbackEdit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackEdit>
 */
class FeedbackEditFactory extends Factory
{
    protected $model = FeedbackEdit::class;

    public function definition(): array
    {
        return [
            'feedback_id' => Feedback::factory(),
            'old_rating' => fake()->numberBetween(1, 5),
            'old_comment' => fake()->optional()->sentence(10),
            'new_rating' => fake()->numberBetween(1, 5),
            'new_comment' => fake()->optional()->sentence(10),
            'edited_by' => User::factory(),
        ];
    }
}
