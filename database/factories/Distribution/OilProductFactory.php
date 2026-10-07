<?php

namespace Database\Factories\Distribution;

use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use App\Models\Distribution\OilProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/** @extends Factory<OilProduct> */
class OilProductFactory extends Factory
{
    protected $model = OilProduct::class;

    public function definition(): array
    {
        return [
            'oil_lot_id' => fn () => DB::table('oil_lots')->insertGetId([
                'harvest_id' => null,
                'lot_code' => 'TEST-LOT-'.fake()->unique()->bothify('########'),
                'extraction_date' => fake()->date(),
                'volume_l' => fake()->randomElement(['250.00', '500.00', '750.00']),
                'grade' => 'Extra virgin',
                'acidity' => '0.300',
                'notes' => 'Chemlali olives from the Sfax region.',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'name' => 'Extra virgin Chemlali olive oil',
            'brand' => 'Sfax Harvest',
            'bottle_volume_ml' => 750,
            'packaging_date' => fake()->date(),
            'image' => null,
            'slug' => null,
            'public_status' => OilProductPublicStatus::Visible,
            'archived_at' => null,
            'created_by_user_id' => User::factory()->state(['role' => Role::Producer]),
        ];
    }

    public function hidden(): static
    {
        return $this->state(['public_status' => OilProductPublicStatus::Hidden]);
    }

    public function archived(): static
    {
        return $this->state([
            'public_status' => OilProductPublicStatus::Hidden,
            'archived_at' => now(),
        ]);
    }
}
