<?php

namespace App\Enums;

enum IrrigationType: string
{
    case Rainfed = 'rainfed';
    case Drip = 'drip';
    case Sprinkler = 'sprinkler';
    case Surface = 'surface';

    public function label(): string
    {
        return $this === self::Rainfed ? 'Rain-fed' : ucfirst($this->value);
    }
}
