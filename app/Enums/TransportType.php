<?php

namespace App\Enums;

enum TransportType: string
{
    case Truck = 'truck';
    case Van = 'van';
    case Rail = 'rail';
    case Ship = 'ship';
    case Air = 'air';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
