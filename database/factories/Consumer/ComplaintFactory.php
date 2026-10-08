<?php

namespace Database\Factories\Consumer;

use App\Enums\Consumer\ComplaintStatus;
use App\Models\Consumer\Complaint;
use App\Models\Distribution\OilProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'oil_product_id' => OilProduct::factory(),
            'consumer_user_id' => User::factory(),
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => ComplaintStatus::Open,
        ];
    }
}
