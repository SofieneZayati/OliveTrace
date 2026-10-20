<?php

namespace App\Services;

use App\Repositories\Distribution\ShipmentRepository;

class DistributorDashboardService
{
    public function __construct(private readonly ShipmentRepository $shipments) {}

    /** @return array{status_counts: array<string, int>, total_distance: float, total_co2: float, average_delivery_days: ?float, overdue_count: int, transport_counts: array<string, int>} */
    public function metrics(?int $userId, ?string $dateFrom, ?string $dateTo): array
    {
        return $this->shipments->dashboard($userId, $dateFrom, $dateTo);
    }
}
