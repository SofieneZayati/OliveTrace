<?php

namespace App\Services\Distribution;

use App\Contracts\OilLotLookup;
use App\Contracts\OilProductEligibility;

class ExistingOilLotEligibility implements OilProductEligibility
{
    public function __construct(private OilLotLookup $lots) {}

    public function isEligible(int $oilLotId): bool
    {
        return $this->lots->exists($oilLotId);
    }
}
