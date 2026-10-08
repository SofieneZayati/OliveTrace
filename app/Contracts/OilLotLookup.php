<?php

namespace App\Contracts;

use App\Data\OilLotSummary;

interface OilLotLookup
{
    public function find(int $id): ?OilLotSummary;

    public function exists(int $id): bool;

    /**
     * Lots may supply several bottle sizes. Pass an owner to limit producer form choices.
     *
     * @return array<int, OilLotSummary>
     */
    public function available(?int $producerUserId = null): array;

    /** @param array<int> $ids
     * @return array<int, OilLotSummary>
     */
    public function findMany(array $ids): array;
}
