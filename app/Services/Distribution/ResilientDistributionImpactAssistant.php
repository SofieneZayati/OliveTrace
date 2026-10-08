<?php

namespace App\Services\Distribution;

use App\Contracts\DistributionImpactAssistant;
use App\Models\Distribution\Shipment;
use Illuminate\Support\Facades\Log;

class ResilientDistributionImpactAssistant implements DistributionImpactAssistant
{
    public function __construct(
        private readonly AiDistributionImpactAssistant $ai,
        private readonly RuleBasedImpactAssistant $fallback,
    ) {}

    public function analyze(Shipment $shipment): ImpactAdvice
    {
        try {
            return $this->ai->analyze($shipment);
        } catch (\Throwable) {
            Log::warning('Distribution impact AI failed; rule-based advice was used.');

            return $this->fallback->analyze($shipment);
        }
    }
}
