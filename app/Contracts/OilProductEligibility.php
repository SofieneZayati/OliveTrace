<?php

namespace App\Contracts;

interface OilProductEligibility
{
    public function isEligible(int $oilLotId): bool;
}
