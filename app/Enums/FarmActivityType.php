<?php

namespace App\Enums;

enum FarmActivityType: string
{
    case Irrigation = 'irrigation';
    case Pruning = 'pruning';
    case SoilCare = 'soil_care';
    case Inspection = 'inspection';

    public function label(): string
    {
        return match ($this) {
            self::Irrigation => 'Irrigation',
            self::Pruning => 'Pruning',
            self::SoilCare => 'Soil care',
            self::Inspection => 'Field inspection',
        };
    }
}
