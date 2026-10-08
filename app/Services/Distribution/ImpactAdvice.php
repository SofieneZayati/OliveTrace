<?php

namespace App\Services\Distribution;

use InvalidArgumentException;

final readonly class ImpactAdvice
{
    public function __construct(
        public string $summary,
        public string $alternative,
        public string $source,
    ) {
        if (! in_array($source, ['ai', 'fallback'], true)) {
            throw new InvalidArgumentException('Impact advice source must be ai or fallback.');
        }
    }

    /**
     * @return array{summary: string, alternative: string, source: string}
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'alternative' => $this->alternative,
            'source' => $this->source,
        ];
    }
}
