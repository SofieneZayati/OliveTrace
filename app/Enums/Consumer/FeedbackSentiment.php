<?php

namespace App\Enums\Consumer;

enum FeedbackSentiment: string
{
    case Positive = 'positive';
    case Neutral = 'neutral';
    case Negative = 'negative';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
