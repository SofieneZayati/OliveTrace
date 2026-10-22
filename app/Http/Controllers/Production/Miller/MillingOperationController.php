<?php

namespace App\Http\Controllers\Production\Miller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\CompleteMillingOperationRequest;
use App\Http\Requests\Production\ConfirmReceptionRequest;
use App\Models\Production\MillingOperation;
use App\Services\Production\MillingOperationManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MillingOperationController extends Controller
{
    public function __construct(private MillingOperationManagement $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', MillingOperation::class);
        $mill = auth()->user()->mill;
        abort_unless($mill, 403);

        $operations = MillingOperation::with(['millRequest.harvest.farm.producerProfile.user', 'millRequest.harvest', 'oilLot'])
            ->whereHas('millRequest', fn ($query) => $query->where('mill_id', $mill->id))
            ->latest()
            ->paginate(15);

        return view('production.milling-operations.index', compact('operations'));
    }

    public function show(MillingOperation $millingOperation): View
    {
        Gate::authorize('view', $millingOperation);
        $millingOperation->load(['millRequest.harvest.farm.producerProfile.user', 'millRequest.mill', 'oilLot', 'statusHistories.changer']);

        return view('production.milling-operations.show', compact('millingOperation'));
    }

    public function confirmReception(ConfirmReceptionRequest $request, MillingOperation $millingOperation): RedirectResponse
    {
        $this->service->confirmReceptionByMiller($request->user(), $millingOperation, $request->validated());

        return back()->with('status', 'Reception confirmed.');
    }

    public function start(Request $request, MillingOperation $millingOperation): RedirectResponse
    {
        $this->service->start($request->user(), $millingOperation);

        return back()->with('status', 'Milling operation started.');
    }

    public function complete(CompleteMillingOperationRequest $request, MillingOperation $millingOperation): RedirectResponse
    {
        $this->service->complete($request->user(), $millingOperation, $request->validated());

        return back()->with('status', 'Milling operation completed and oil lot created.');
    }
}
