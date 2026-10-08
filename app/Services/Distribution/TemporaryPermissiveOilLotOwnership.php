<?php

namespace App\Services\Distribution;

use App\Contracts\OilLotOwnership;
use App\Models\Production\OilLot;

/**
 * Real ownership check using Mariem's oil_lots table (producer_user_id column).
 */
class TemporaryPermissiveOilLotOwnership implements OilLotOwnership
{
    public function belongsToUser(int $oilLotId, int $userId): bool
    {
        $lot = OilLot::find($oilLotId);
        if ($lot === null) {
            return false;
        }

        return $lot->isOwnedBy($userId);
    }
}
