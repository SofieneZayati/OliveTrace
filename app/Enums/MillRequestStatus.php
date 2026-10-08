<?php

namespace App\Enums;

enum MillRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    /** Statuses that still hold quantity on a harvest. */
    public const ACTIVE = ['pending', 'accepted', 'completed'];

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isActive(): bool
    {
        return in_array($this->value, self::ACTIVE, true);
    }
}
