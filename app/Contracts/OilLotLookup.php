<?php

namespace App\Contracts;

use App\Data\OilLotSummary;

interface OilLotLookup
{
    public function find(int $id): ?OilLotSummary;

    public function exists(int $id): bool;

    /** @return array<OilLotSummary> */
    public function available(): array;
}
