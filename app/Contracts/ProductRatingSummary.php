<?php

namespace App\Contracts;

use App\Data\RatingSummary;

interface ProductRatingSummary
{
    public function summary(int $productId): ?RatingSummary;
}
