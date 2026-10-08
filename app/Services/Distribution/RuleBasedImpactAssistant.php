<?php

namespace App\Services\Distribution;

use App\Enums\TransportType;
use App\Models\Distribution\Shipment;

class RuleBasedImpactAssistant
{
    public function analyze(Shipment $shipment): ImpactAdvice
    {
        $shipment->loadMissing('oilProduct');
        $factors = config('olivetrace-distribution.co2_emission_factors', []);
        $currentType = $shipment->transport_type instanceof TransportType
            ? $shipment->transport_type->value
            : (string) $shipment->transport_type;
        $currentFactor = (float) ($factors[$currentType] ?? 0);
        $referenceTypes = ['rail', 'ship'];
        $alternatives = array_filter(
            $referenceTypes,
            fn (string $type): bool => isset($factors[$type]) && (float) $factors[$type] < $currentFactor,
        );
        $alternativeType = $alternatives === []
            ? null
            : array_reduce($alternatives, fn (?string $lowest, string $type): string => $lowest === null
                || (float) $factors[$type] < (float) $factors[$lowest] ? $type : $lowest);

        $co2 = (float) $shipment->co2_estimate;
        $summary = sprintf(
            'This %s shipment covers %.1f km and carries %s bottles, with an estimated %.2f kg CO₂. This is a rule-based estimate from the configured transport factor, not a measured footprint.',
            $currentType,
            (float) $shipment->distance_km,
            number_format((int) $shipment->quantity_bottles),
            $co2,
        );

        if ($alternativeType === null || $currentFactor <= 0) {
            $alternative = 'Ship is already the lowest-emission option among rail and ship in the configured factors; no lower-impact rail-or-ship alternative is available for this route.';
        } else {
            $factor = (float) $factors[$alternativeType];
            $savingPercent = round((1 - ($factor / $currentFactor)) * 100);
            $alternativeCo2 = round($co2 * ($factor / $currentFactor), 2);
            $alternative = sprintf(
                'Consider %s instead: the configured factor estimates about %d%% lower CO₂ (approximately %.2f kg instead of %.2f kg for the same route and load).',
                $alternativeType,
                $savingPercent,
                $alternativeCo2,
                $co2,
            );
        }

        return new ImpactAdvice($summary, $alternative, 'fallback');
    }
}
