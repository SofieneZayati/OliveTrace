<?php

namespace App\Repositories\Production;

use App\Models\Production\ProducerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProducerProfiles
{
    public function forUser(int $userId): ?ProducerProfile
    {
        return ProducerProfile::where('user_id', $userId)->first();
    }

    public function find(int $id): ProducerProfile
    {
        return ProducerProfile::with('farms')->findOrFail($id);
    }

    public function paginate(array $filters, int $page): LengthAwarePaginator
    {
        $query = ProducerProfile::withCount('farms');
        if ($filters['search'] ?? null) {
            $search = '%'.mb_strtolower($filters['search']).'%';
            $query->where(fn (Builder $profiles) => $profiles->whereRaw('LOWER(display_name) LIKE ?', [$search])
                ->orWhereRaw('LOWER(company_name) LIKE ?', [$search]));
        }
        if (isset($filters['active'])) {
            $query->where('is_active', (bool) $filters['active']);
        }

        return $query->orderBy('display_name')->orderBy('id')->paginate(12, ['*'], 'page', $page)->withQueryString();
    }
}
