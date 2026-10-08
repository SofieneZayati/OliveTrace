<?php

namespace App\Enums;

enum HarvestStatus: string
{
    case Declared = 'declared';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Milled = 'milled';

    /** Statuses a producer may pick. Milled is unlocked when a mill finishes the request. */
    public const PRODUCER_SETTABLE = ['declared', 'in_progress', 'completed'];

    public function label(): string
    {
        return match ($this) {
            self::Declared => 'Declared',
            self::InProgress => 'In progress',
            self::Completed => 'Harvest completed',
            self::Milled => 'Milled',
        };
    }

    public function isProducerSettable(): bool
    {
        return in_array($this->value, self::PRODUCER_SETTABLE, true);
    }
}
