<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Mill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mill>
 */
class MillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Miller]),
            'name' => 'Huilerie '.fake()->lastName(),
            'region' => fake()->randomElement([
                'Sfax', 'Sousse', 'Monastir', 'Mahdia', 'Kairouan', 'Médenine', 'Zaghouan', 'Nabeul',
            ]),
            'extraction_type' => fake()->randomElement(Mill::EXTRACTION_TYPES),
            'capacity' => fake()->numberBetween(500, 5000),
            'contact' => '+216 '.fake()->numerify('## ### ###'),
        ];
    }
}
