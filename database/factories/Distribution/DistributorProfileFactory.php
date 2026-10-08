<?php

namespace Database\Factories\Distribution;

use App\Enums\Role;
use App\Models\Distribution\DistributorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DistributorProfile> */
class DistributorProfileFactory extends Factory
{
    protected $model = DistributorProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Distributor]),
            'company_name' => fake()->company().' Olive Logistics',
            'address' => 'Route de Tunis, Sfax',
            'phone' => '+216 74 000 000',
            'region' => 'Sfax',
            'is_active' => true,
        ];
    }
}
