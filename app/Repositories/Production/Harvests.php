<?php

namespace App\Repositories\Production;

use App\Models\Production\Harvest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class Harvests
{
    public function find(int $id): Harvest
    {
        return Harvest::with(['farm.producerProfile.user', 'millRequests.mill', 'millingRequest'])->findOrFail($id);
    }

    public function forFarm(int $farmId): array
    {
        return Harvest::with(['millRequests.mill', 'millingRequest'])
            ->where('farm_id', $farmId)
            ->orderByDesc('harvest_date')->orderByDesc('id')
            ->get()->all();
    }

    public function paginate(array $filters, int $page, ?int $userId = null): LengthAwarePaginator
    {
        $query = Harvest::with(['farm.producerProfile', 'millingRequest']);
        if ($userId !== null) {
            $query->whereHas('farm.producerProfile', fn (Builder $profiles) => $profiles->where('user_id', $userId));
        }
        if ($filters['search'] ?? null) {
            $search = '%'.mb_strtolower($filters['search']).'%';
            $query->where(fn (Builder $harvests) => $harvests
                ->whereRaw('LOWER(notes) LIKE ?', [$search])
                ->orWhereHas('farm', fn (Builder $farms) => $farms->whereRaw('LOWER(name) LIKE ?', [$search]))
                ->orWhereHas('farm.producerProfile', fn (Builder $profiles) => $profiles->whereRaw('LOWER(display_name) LIKE ?', [$search])));
        }
        foreach (['status', 'method'] as $field) {
            if ($filters[$field] ?? null) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query->orderByDesc('harvest_date')->orderByDesc('id')->paginate(12, ['*'], 'page', $page)->withQueryString();
    }
}
