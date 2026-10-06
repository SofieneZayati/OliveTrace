<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Producer = 'producer';
    case Miller = 'miller';
    case Laboratory = 'laboratory';
    case Distributor = 'distributor';
    case Consumer = 'consumer';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
