<?php

namespace App\Enums\Production;

enum OliveCondition: string
{
    case Good = 'good';
    case Acceptable = 'acceptable';
    case Poor = 'poor';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Good',
            self::Acceptable => 'Acceptable',
            self::Poor => 'Poor',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
