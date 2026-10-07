<?php

namespace App\Enums;

enum FarmStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Disabled = 'disabled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
