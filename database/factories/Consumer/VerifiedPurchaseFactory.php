<?php

namespace Database\Factories\Consumer;

use App\Models\Consumer\VerifiedPurchase;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerifiedPurchase>
 */
class VerifiedPurchaseFactory extends Factory
{
    protected $model = VerifiedPurchase::class;

    public function definition(): array
    {
        return [
            'consumer_user_id' => User::factory(),
            'oil_product_id' => OilProduct::factory(),
            'shipment_id' => Shipment::factory(),
            'proof_type' => fake()->randomElement(['receipt', 'qr_code', 'invoice']),
            'proof_path' => null,
            'verified_at' => now(),
        ];
    }
}
