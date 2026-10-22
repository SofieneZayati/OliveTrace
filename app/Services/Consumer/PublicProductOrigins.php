<?php

namespace App\Services\Consumer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

// Read-only catalog projection. Only opted-in origins from active producers
// are selected; no contact fields, private notes or farm GPS are queried.
class PublicProductOrigins
{
    public function join(Builder $query): Builder
    {
        return $query
            ->leftJoin('oil_lots as origin_lots', 'origin_lots.id', '=', 'oil_products.oil_lot_id')
            ->leftJoin('milling_operations as origin_operations', fn ($join) => $join
                ->on('origin_operations.id', '=', 'origin_lots.milling_operation_id')->whereNull('origin_operations.deleted_at'))
            ->leftJoin('mill_requests as origin_requests', 'origin_requests.id', '=', DB::raw('COALESCE(origin_lots.mill_request_id, origin_operations.mill_request_id)'))
            ->leftJoin('harvests as origin_harvests', fn ($join) => $join
                ->on('origin_harvests.id', '=', 'origin_requests.harvest_id')->whereNull('origin_harvests.deleted_at'))
            ->leftJoin('farms as origin_farms', fn ($join) => $join
                ->on('origin_farms.id', '=', 'origin_harvests.farm_id')->where('origin_farms.is_public', true)->where('origin_farms.status', 'active'))
            ->leftJoin('producer_profiles as origin_profiles', fn ($join) => $join
                ->on('origin_profiles.id', '=', 'origin_farms.producer_profile_id')->where('origin_profiles.is_public', true)->where('origin_profiles.is_active', true))
            ->leftJoin('users as origin_users', fn ($join) => $join
                ->on('origin_users.id', '=', 'origin_profiles.user_id')->where('origin_users.is_active', true)->where('origin_users.role', 'producer'));
    }

    public function columns(): array
    {
        return collect([
            'origin_name' => 'origin_farms.name', 'origin_region' => 'origin_farms.governorate',
            'origin_variety' => 'origin_farms.olive_variety', 'origin_producer' => 'origin_profiles.display_name',
        ])->map(fn ($column, $alias) => DB::raw("CASE WHEN origin_users.id IS NOT NULL THEN {$column} ELSE NULL END AS {$alias}"))->values()->all();
    }

    public function point(string $region, string $url, int $count = 1): ?array
    {
        $point = config('public-origin-regions.'.$region);

        return $point ? [...$point, 'name' => $region, 'region' => 'Tunisia · approximate region',
            'status' => $count.' product(s) on this page', 'url' => $url, 'linkLabel' => 'Explore oils from '.$region.' →'] : null;
    }
}
