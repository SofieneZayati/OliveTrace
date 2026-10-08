<?php

namespace App\Services\Consumer;

use App\Contracts\ProductRatingSummary;
use App\Data\RatingSummary;
use App\Models\Consumer\Feedback;

// Module 5 implementation of Hana's ProductRatingSummary contract: the public
// catalog reads the average and count of visible consumer reviews through
// this service instead of querying Module 5 tables directly.
//
// Performance: the catalog renders up to twelve cards per page, so all
// summaries are aggregated in a single grouped query and memoized for the
// request instead of querying once per product.
class FeedbackRatingSummary implements ProductRatingSummary
{
    /** @var array<int, RatingSummary>|null */
    private ?array $cache = null;

    public function summary(int $productId): ?RatingSummary
    {
        return $this->all()[$productId] ?? null;
    }

    /** @return array<int, RatingSummary> */
    private function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $map = [];
        foreach (Feedback::query()->visible()
            ->selectRaw('oil_product_id, AVG(rating) AS average_rating, COUNT(*) AS ratings_count')
            ->groupBy('oil_product_id')
            ->get() as $row) {
            $map[(int) $row->oil_product_id] = new RatingSummary((float) round($row->average_rating, 1), (int) $row->ratings_count);
        }

        return $this->cache = $map;
    }
}
