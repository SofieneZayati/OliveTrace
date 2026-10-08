<?php

namespace App\Data;

final readonly class RatingSummary
{
    public function __construct(
        public float $average,
        public int $count,
    ) {}
}
