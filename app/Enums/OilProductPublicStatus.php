<?php

namespace App\Enums;

enum OilProductPublicStatus: string
{
    case Visible = 'visible';
    case Hidden = 'hidden';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
