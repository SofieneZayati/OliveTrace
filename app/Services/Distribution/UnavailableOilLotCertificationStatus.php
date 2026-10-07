<?php

namespace App\Services\Distribution;

use App\Contracts\OilLotCertificationStatusProvider;

class UnavailableOilLotCertificationStatus implements OilLotCertificationStatusProvider
{
    public function statusFor(int $oilLotId): string
    {
        return 'not available';
    }
}
