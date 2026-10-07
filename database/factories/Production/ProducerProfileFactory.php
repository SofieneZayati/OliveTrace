<?php

namespace Database\Factories\Production;

use App\Enums\Role;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProducerProfile> */
class ProducerProfileFactory extends Factory
{
    protected $model = ProducerProfile::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id' => User::factory()->state(['role' => Role::Producer]),
            'display_name' => $name, 'company_name' => $name,
            'description' => 'An olive-growing family sharing the origin of its harvests.',
            'is_active' => true, 'is_public' => false,
        ];
    }
}
