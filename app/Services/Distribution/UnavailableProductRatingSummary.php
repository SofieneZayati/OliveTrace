<?php

namespace App\Services\Distribution;

use App\Contracts\ProductRatingSummary;
use App\Data\RatingSummary;

class UnavailableProductRatingSummary implements ProductRatingSummary
{
    public function summary(int $productId): ?RatingSummary
    {
        return null;
    }
}
