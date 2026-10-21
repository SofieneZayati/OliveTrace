<?php

namespace Database\Factories\Consumer;

use App\Models\Consumer\ConsumerNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumerNotification>
 */
class ConsumerNotificationFactory extends Factory
{
    protected $model = ConsumerNotification::class;

    public function definition(): array
    {
        return [
            'consumer_user_id' => User::factory(),
            'type' => fake()->randomElement(['new_certificate', 'complaint_status', 'review_reply', 'product_alert']),
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'related_feedback_id' => null,
            'related_complaint_id' => null,
            'related_oil_product_id' => null,
            'read' => fake()->boolean(30),
        ];
    }
}
