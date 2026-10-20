<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\ShipmentStatus;
use App\Enums\TransportType;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DistributorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_sees_only_their_metrics_and_admin_sees_all_metrics(): void
    {
        $profileA = DistributorProfile::factory()->create();
        $profileB = DistributorProfile::factory()->create();
        $this->shipment($profileA, ShipmentStatus::Delivered, TransportType::Truck, -4, -2, 270, 12.50);
        $this->shipment($profileA, ShipmentStatus::InTransit, TransportType::Van, -3, null, 190, 8.00);
        $this->shipment($profileB, ShipmentStatus::Planned, TransportType::Rail, -10, null, 500, 4.00);

        $this->actingAs($profileA->user)
            ->get(route('distributor.dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', function (array $metrics): bool {
                return $metrics['status_counts'][ShipmentStatus::Delivered->value] === 1
                    && $metrics['status_counts'][ShipmentStatus::InTransit->value] === 1
                    && $metrics['total_distance'] === 460.0
                    && $metrics['total_co2'] === 20.5;
            })
            ->assertSee('Only shipments linked to your distributor profile.');

        $admin = User::factory()->state(['role' => Role::Admin])->create();
        $this->actingAs($admin)->get(route('admin.distributor.dashboard'))
            ->assertOk()
            ->assertViewHas('metrics.total_distance', 960.0);
    }

    public function test_dashboard_calculates_delivery_delay_overdue_and_date_filter(): void
    {
        $profile = DistributorProfile::factory()->create();
        $this->shipment($profile, ShipmentStatus::Delivered, TransportType::Truck, -6, -3, 100, 5.00);
        $this->shipment($profile, ShipmentStatus::InTransit, TransportType::Ship, -5, null, 200, 7.00);
        $this->shipment($profile, ShipmentStatus::Planned, TransportType::Van, 2, null, 300, 9.00);

        $this->actingAs($profile->user)
            ->get(route('distributor.dashboard', ['date_from' => now()->subDays(7)->toDateString(), 'date_to' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertViewHas('metrics', function (array $metrics): bool {
                return $metrics['average_delivery_days'] === 3.0
                    && $metrics['overdue_count'] === 1
                    && $metrics['transport_counts']['truck'] === 1
                    && $metrics['transport_counts']['ship'] === 1;
            });

        $this->actingAs($profile->user)
            ->get(route('distributor.dashboard', ['date_from' => now()->toDateString(), 'date_to' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('date_to');
    }

    public function test_dashboard_is_restricted_to_distributors_and_admins(): void
    {
        $this->get(route('distributor.dashboard'))->assertRedirect(route('login'));
        $consumer = User::factory()->state(['role' => Role::Consumer])->create();
        $this->actingAs($consumer)->get(route('distributor.dashboard'))->assertForbidden();
    }

    private function shipment(
        DistributorProfile $profile,
        ShipmentStatus $status,
        TransportType $transport,
        int $departureOffset,
        ?int $arrivalOffset,
        float $distance,
        float $co2,
    ): Shipment {
        $shipment = Shipment::factory()->for($profile)->create([
            'departure_date' => now()->addDays($departureOffset)->toDateString(),
            'arrival_date' => $arrivalOffset === null ? null : now()->addDays($arrivalOffset)->toDateString(),
            'status' => $status,
            'transport_type' => $transport,
            'distance_km' => $distance,
        ]);
        DB::table('shipments')->where('id', $shipment->id)->update(['co2_estimate' => $co2]);

        return $shipment->fresh();
    }
}
