<?php

namespace App\Repositories\Production;

use App\Enums\FarmStatus;
use App\Enums\Role;
use App\Models\Production\Farm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class Farms
{
    public function find(int $id): Farm
    {
        return Farm::with('producerProfile.user')->findOrFail($id);
    }

    /** Mariem can select eligible farms by producer ID, without copying origin fields. @return list<Farm> */
    public function selectableForUser(int $userId): array
    {
        return Farm::with('producerProfile')
            ->where('status', FarmStatus::Active)
            ->whereHas('producerProfile', function (Builder $query) use ($userId) {
                $query->where('user_id', $userId)->where('is_active', true)
                    ->whereHas('user', fn (Builder $users) => $users
                        ->where('role', Role::Producer)->where('is_active', true));
            })
            ->orderBy('name')->orderBy('id')->get()->all();
    }

    public function paginate(array $filters, int $page, ?int $userId = null): LengthAwarePaginator
    {
        $query = Farm::with('producerProfile');
        if ($userId !== null) {
            $query->whereHas('producerProfile', fn (Builder $profiles) => $profiles->where('user_id', $userId));
        }
        if ($filters['search'] ?? null) {
            $search = '%'.mb_strtolower($filters['search']).'%';
            $query->where(fn (Builder $farms) => $farms->whereRaw('LOWER(name) LIKE ?', [$search])
                ->orWhereHas('producerProfile', fn (Builder $profiles) => $profiles->whereRaw('LOWER(display_name) LIKE ?', [$search])));
        }
        foreach (['governorate', 'status'] as $field) {
            if ($filters[$field] ?? null) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query->orderBy('name')->orderBy('id')->paginate(12, ['*'], 'page', $page)->withQueryString();
    }
}
