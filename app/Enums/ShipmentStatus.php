<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Planned = 'planned';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::InTransit => 'In transit',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }
}
