<?php

namespace App\Http\Controllers\Production;

use App\Enums\FarmStatus;
use App\Http\Controllers\Controller;
use App\Models\Production\Farm;
use App\Repositories\Production\Farms;
use App\Support\ProductionOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FarmMapController extends Controller
{
    public function __invoke(Request $request, Farms $farms)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'governorate' => ['nullable', Rule::in(ProductionOptions::GOVERNORATES)],
            'status' => ['nullable', Rule::enum(FarmStatus::class)],
        ]);
        $admin = $request->routeIs('admin.*');
        $data = $farms->mapData($filters, $admin ? null : $request->user()->id);

        return response()->view('production.farms.map', [
            'admin' => $admin, 'total' => $data['total'], 'located' => $data['located'],
            'limit' => Farms::MAP_LIMIT,
            'points' => $data['farms']->map(fn (Farm $farm) => [
                'lat' => (float) $farm->gps_lat, 'lng' => (float) $farm->gps_lng,
                'name' => $farm->name, 'region' => $farm->governorate,
                'status' => $farm->status->label(),
                'url' => route($admin ? 'admin.farms.show' : 'producer.farms.show', $farm->id),
            ])->all(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
