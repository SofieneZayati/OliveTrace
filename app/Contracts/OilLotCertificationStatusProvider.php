<?php

namespace App\Contracts;

interface OilLotCertificationStatusProvider
{
    public function statusFor(int $oilLotId): string;
}
