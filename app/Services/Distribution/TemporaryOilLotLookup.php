<?php

// TEMPORARY - replaced when Mariem's real OilLot is merged. Only this class changes.

namespace App\Services\Distribution;

use App\Contracts\OilLotLookup;
use App\Data\OilLotSummary;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class TemporaryOilLotLookup implements OilLotLookup
{
    public function find(int $id): ?OilLotSummary
    {
        $row = DB::table('oil_lots')->where('id', $id)->first();
        if ($row === null) {
            return null;
        }

        return new OilLotSummary(
            id: (int) $row->id,
            lotCode: $row->lot_code,
            extractionDate: new DateTimeImmutable($row->extraction_date),
            volumeL: (string) $row->volume_l,
            grade: $row->grade,
            acidity: (string) $row->acidity,
        );
    }

    public function exists(int $id): bool
    {
        return DB::table('oil_lots')->where('id', $id)->exists();
    }
}
