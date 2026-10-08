<?php

namespace App\Data;

use DateTimeImmutable;

final readonly class OilLotSummary
{
    public function __construct(
        public int $id,
        public string $lotCode,
        public ?DateTimeImmutable $extractionDate,
        public ?string $volumeL,
        public string $grade,
        public ?string $acidity,
    ) {}
}
