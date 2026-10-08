<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\OilLot;
use Illuminate\View\View;

class OilLotController extends Controller
{
    public function index(): View
    {
        $oilLots = OilLot::query()
            ->whereHas('millRequest.harvest.farm.producerProfile', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['millRequest.harvest.farm'])
            ->latest('production_date')
            ->paginate(12)
            ->withQueryString();

        return view('production.oil-lots.index', ['oilLots' => $oilLots]);
    }

    public function show(OilLot $oilLot): View
    {
        $this->authorize('view', $oilLot);

        $oilLot->load(['millRequest.harvest.farm.producerProfile.user']);

        return view('production.oil-lots.show', ['oilLot' => $oilLot]);
    }
}
