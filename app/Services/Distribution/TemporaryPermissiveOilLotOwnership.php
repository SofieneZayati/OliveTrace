<?php

namespace App\Services\Distribution;

use App\Contracts\OilLotOwnership;

/**
 * TEMPORARY: Mariem's OilLot ownership model is not available yet.
 */
class TemporaryPermissiveOilLotOwnership implements OilLotOwnership
{
    public function belongsToUser(int $oilLotId, int $userId): bool
    {
        return true;
    }
}
