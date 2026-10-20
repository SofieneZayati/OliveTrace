<?php

namespace App\Repositories\Distribution;

use App\Enums\ShipmentStatus;
use App\Models\Distribution\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShipmentRepository
{
    /**
     * @return array{status_counts: array<string, int>, total_distance: float, total_co2: float, average_delivery_days: ?float, overdue_count: int, transport_counts: array<string, int>}
     */
    public function dashboard(?int $userId, ?string $dateFrom, ?string $dateTo): array
    {
        $query = $this->filteredQuery($userId, $dateFrom, $dateTo);
        $statusCounts = (clone $query)
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();

        $totals = (clone $query)->selectRaw(
            'COALESCE(SUM(distance_km), 0) as total_distance, COALESCE(SUM(co2_estimate), 0) as total_co2'
        )->first();

        $dateDifference = DB::getDriverName() === 'sqlite'
            ? 'julianday(arrival_date) - julianday(departure_date)'
            : 'DATEDIFF(arrival_date, departure_date)';
        $averageDeliveryDays = (clone $query)
            ->where('status', ShipmentStatus::Delivered->value)
            ->whereNotNull('arrival_date')
            ->selectRaw("AVG({$dateDifference}) as average_delivery_days")
            ->value('average_delivery_days');

        $transportCounts = (clone $query)
            ->select('transport_type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('transport_type')
            ->pluck('aggregate', 'transport_type')
            ->map(fn ($count): int => (int) $count)
            ->all();

        $overdueCount = (clone $query)
            ->whereIn('status', [ShipmentStatus::Planned->value, ShipmentStatus::InTransit->value])
            ->whereDate('departure_date', '<', today())
            ->count();

        return [
            'status_counts' => $statusCounts,
            'total_distance' => (float) $totals->total_distance,
            'total_co2' => (float) $totals->total_co2,
            'average_delivery_days' => $averageDeliveryDays === null ? null : (float) $averageDeliveryDays,
            'overdue_count' => $overdueCount,
            'transport_counts' => $transportCounts,
        ];
    }

    private function filteredQuery(?int $userId, ?string $dateFrom, ?string $dateTo): Builder
    {
        return Shipment::query()
            ->when($userId !== null, fn (Builder $query) => $query->forDistributorUser($userId))
            ->when($dateFrom !== null, fn (Builder $query) => $query->whereDate('departure_date', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query) => $query->whereDate('departure_date', '<=', $dateTo));
    }
}
