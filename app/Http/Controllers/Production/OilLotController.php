<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\OilLot;
use Illuminate\View\View;

class OilLotController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $query = OilLot::query()
            ->where(function ($scope) use ($userId) {
                $scope->where('producer_user_id', $userId)
                    ->orWhereHas('millRequest.harvest.farm.producerProfile', fn ($q) => $q->where('user_id', $userId));
            })
            ->with(['millRequest.harvest.farm']);

        if ($search = trim((string) request('search'))) {
            $query->where(function ($scope) use ($search) {
                $scope->where('lot_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('millRequest.harvest', fn ($q) => $q->where('notes', 'like', "%{$search}%"))
                    ->orWhereHas('millRequest.harvest.farm', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($quality = request('quality')) {
            $query->where('quality_grade', $quality);
        }

        $oilLots = $query->latest('production_date')->paginate(12)->withQueryString();

        return view('production.oil-lots.index', ['oilLots' => $oilLots]);
    }

    public function show(OilLot $oilLot): View
    {
        $this->authorize('view', $oilLot);

        $oilLot->load(['millRequest.harvest.farm.producerProfile.user', 'certificateRequests']);

        return view('production.oil-lots.show', ['oilLot' => $oilLot]);
    }
}
