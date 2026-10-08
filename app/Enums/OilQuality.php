<?php

namespace App\Enums;

enum OilQuality: string
{
    case ExtraVirgin = 'extra_virgin';
    case Virgin = 'virgin';
    case Lampante = 'lampante';

    public function label(): string
    {
        return match ($this) {
            self::ExtraVirgin => 'Extra Virgin',
            self::Virgin => 'Virgin',
            self::Lampante => 'Lampante',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
