<?php

namespace App\Enums\Consumer;

enum FeedbackStatus: string
{
    case Visible = 'visible';
    case Hidden = 'hidden';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
