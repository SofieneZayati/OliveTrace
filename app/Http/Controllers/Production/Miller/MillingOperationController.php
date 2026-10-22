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
    public function __construct(private MillingOperationManagement $service)
    {
    }

    public function index(): View
    {
        Gate::authorize('viewAny', MillingOperation::class);
        $user = auth()->user();
        $millId = $user->mill?->id ?? $user->mill_id;
        $operations = MillingOperation::with(['millRequest.harvest.farm.producerProfile.user', 'millRequest.harvest', 'oilLot'])
            ->when($millId, fn ($q) => $q->whereHas('millRequest', fn ($qq) => $qq->where('mill_id', $millId)))
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
