<?php

namespace Database\Factories\Distribution;

use App\Enums\ShipmentStatus;
use App\Enums\TransportType;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shipment> */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'oil_product_id' => OilProduct::factory(),
            'distributor_profile_id' => DistributorProfile::factory(),
            'departure_location' => 'Sfax, Tunisia',
            'destination' => 'Tunis, Tunisia',
            'departure_date' => now()->toDateString(),
            'arrival_date' => null,
            'distance_km' => '270.00',
            'transport_type' => TransportType::Truck,
            'status' => ShipmentStatus::Planned,
            'co2_estimate' => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state([
            'arrival_date' => now()->toDateString(),
            'status' => ShipmentStatus::Delivered,
        ]);
    }

    public function planned(): static
    {
        return $this->state([
            'arrival_date' => null,
            'status' => ShipmentStatus::Planned,
        ]);
    }
}
