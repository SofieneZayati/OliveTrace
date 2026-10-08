<?php

namespace Database\Factories\Distribution;

use App\Enums\OilProductPublicStatus;
use App\Enums\Role;
use App\Models\Distribution\OilProduct;
use App\Models\Production\Farm;
use App\Models\Production\Harvest;
use App\Models\Production\MillRequest;
use App\Models\Production\OilLot;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OilProduct> */
class OilProductFactory extends Factory
{
    protected $model = OilProduct::class;

    public function definition(): array
    {
        return [
            'created_by_user_id' => User::factory()->state(['role' => Role::Producer]),
            'oil_lot_id' => function (array $attributes) {
                $user = User::findOrFail($attributes['created_by_user_id']);
                $profile = $user->producerProfile ?? ProducerProfile::factory()->for($user)->create();
                $harvest = Harvest::factory()->for(Farm::factory()->for($profile, 'producerProfile'))->create();
                $request = MillRequest::factory()->for($harvest)->create(['status' => 'completed']);

                return OilLot::factory()->for($request, 'millRequest')->create(['producer_user_id' => $user->id])->id;
            },
            'name' => 'Extra virgin Chemlali olive oil',
            'brand' => 'Sfax Harvest',
            'bottle_volume_ml' => 750,
            'packaging_date' => fake()->date(),
            'image' => null,
            'public_status' => OilProductPublicStatus::Visible,
            'archived_at' => null,
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
