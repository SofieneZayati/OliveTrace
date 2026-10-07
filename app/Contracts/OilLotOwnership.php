<?php

namespace App\Contracts;

interface OilLotOwnership
{
    public function belongsToUser(int $oilLotId, int $userId): bool;
}
