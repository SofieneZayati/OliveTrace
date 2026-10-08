<?php

namespace App\Services\Distribution;

use InvalidArgumentException;

class Co2Estimator
{
    /**
     * Estimates shipment kg CO2 from oil and packaging mass moved over the route.
     *
     * @throws InvalidArgumentException when inputs are invalid or a transport factor is missing
     */
    public function estimate(float $distanceKm, string $transportType, int $quantityBottles, int $bottleVolumeMl): float
    {
        if (! is_finite($distanceKm) || $distanceKm <= 0 || $quantityBottles < 1 || $bottleVolumeMl < 1) {
            throw new InvalidArgumentException('Distance, bottle quantity, and bottle volume must be positive finite values.');
        }

        $factor = config("olivetrace-distribution.co2_emission_factors.{$transportType}");
        if (! is_numeric($factor) || (float) $factor <= 0) {
            throw new InvalidArgumentException('A positive CO2 emission factor is required for the transport type.');
        }

        $oilAndPackagingKg = ($quantityBottles * ($bottleVolumeMl / 1000) * 0.916)
            + ($quantityBottles * 0.5);
        $transportedTonnes = $oilAndPackagingKg / 1000;

        return round($distanceKm * $transportedTonnes * (float) $factor, 2);
    }
}
