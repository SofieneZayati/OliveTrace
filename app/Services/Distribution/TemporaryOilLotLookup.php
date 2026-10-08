<?php

namespace App\Services\Distribution;

use App\Contracts\OilLotLookup;
use App\Data\OilLotSummary;
use App\Models\Production\OilLot;
use DateTimeImmutable;

/**
 * Real OilLot lookup using Mariem's oil_lots table.
 */
class TemporaryOilLotLookup implements OilLotLookup
{
    public function find(int $id): ?OilLotSummary
    {
        $lot = OilLot::find($id);
        if ($lot === null) {
            return null;
        }

        return $this->toSummary($lot);
    }

    public function exists(int $id): bool
    {
        return OilLot::where('id', $id)->exists();
    }

    public function available(?int $producerUserId = null): array
    {
        return OilLot::query()
            ->when($producerUserId !== null, fn ($query) => $query->where('producer_user_id', $producerUserId))
            ->orderBy('oil_lots.lot_number')
            ->get(['oil_lots.*'])
            ->mapWithKeys(fn (OilLot $lot) => [(int) $lot->id => $this->toSummary($lot)])
            ->all();
    }

    /** @param array<int> $ids */
    public function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return OilLot::whereIn('id', array_unique(array_map('intval', $ids)))
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (OilLot $lot) => [(int) $lot->id => $this->toSummary($lot)])
            ->all();
    }

    private function toSummary(OilLot $lot): OilLotSummary
    {
        return new OilLotSummary(
            id: (int) $lot->id,
            lotCode: $lot->lot_number,
            extractionDate: $lot->production_date ? new DateTimeImmutable($lot->production_date->format('Y-m-d')) : null,
            volumeL: $lot->liters,
            grade: $lot->quality_grade?->value ?? 'unknown',
            acidity: null, // Acidity belongs to laboratory analyses; unknown is never zero.
        );
    }
}
