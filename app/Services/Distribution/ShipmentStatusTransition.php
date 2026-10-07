<?php

namespace App\Services\Distribution;

use App\Enums\ShipmentStatus;

class ShipmentStatusTransition
{
    public function allows(ShipmentStatus|string $current, ShipmentStatus|string $next): bool
    {
        $current = $current instanceof ShipmentStatus ? $current : ShipmentStatus::tryFrom($current);
        $next = $next instanceof ShipmentStatus ? $next : ShipmentStatus::tryFrom($next);

        if ($current === null || $next === null) {
            return false;
        }

        if ($current === $next) {
            return true;
        }

        return match ($current) {
            ShipmentStatus::Planned => in_array($next, [ShipmentStatus::InTransit, ShipmentStatus::Cancelled], true),
            ShipmentStatus::InTransit => in_array($next, [ShipmentStatus::Delivered, ShipmentStatus::Cancelled], true),
            ShipmentStatus::Delivered, ShipmentStatus::Cancelled => false,
        };
    }
}
