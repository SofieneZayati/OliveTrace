<?php

namespace App\Enums\Consumer;

enum FeedbackCategory: string
{
    case Quality = 'quality';
    case Taste = 'taste';
    case Packaging = 'packaging';
    case Delivery = 'delivery';
    case Authenticity = 'authenticity';
    case Service = 'service';
    case Price = 'price';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
