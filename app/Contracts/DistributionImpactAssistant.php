<?php

namespace App\Contracts;

use App\Models\Distribution\Shipment;
use App\Services\Distribution\ImpactAdvice;

interface DistributionImpactAssistant
{
    public function analyze(Shipment $shipment): ImpactAdvice;
}
