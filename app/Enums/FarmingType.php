<?php

namespace App\Enums;

enum FarmingType: string
{
    case Conventional = 'conventional';
    case Organic = 'organic';
    case Integrated = 'integrated';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
