<?php

namespace App\Enums\Consumer;

enum ComplaintStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InReview => 'In review',
            self::Resolved => 'Resolved',
            self::Rejected => 'Rejected',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Resolved, self::Rejected], true);
    }
}
