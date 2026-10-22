<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\AcknowledgeDiscrepancyRequest;
use App\Http\Requests\Production\ConfirmDeliveryRequest;
use App\Models\Production\MillingOperation;
use App\Services\Production\MillingOperationManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MillingOperationController extends Controller
{
    public function __construct(private MillingOperationManagement $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', MillingOperation::class);
        $user = auth()->user();
        $producerUserId = $user->producerProfile?->user_id ?? $user->id;
        $operations = MillingOperation::with(['millRequest.harvest.farm.producerProfile.user', 'millRequest.mill', 'oilLot'])
            ->whereHas('millRequest.harvest.farm.producerProfile', fn ($q) => $q->where('user_id', $producerUserId))
            ->latest()
            ->paginate(15);

        return view('production.milling-operations.producer-index', compact('operations'));
    }

    public function show(MillingOperation $millingOperation): View
    {
        Gate::authorize('view', $millingOperation);
        $millingOperation->load(['millRequest.harvest.farm.producerProfile.user', 'millRequest.mill', 'oilLot', 'statusHistories.changer']);

        return view('production.milling-operations.producer-show', compact('millingOperation'));
    }

    public function confirmDelivery(ConfirmDeliveryRequest $request, MillingOperation $millingOperation): RedirectResponse
    {
        $this->service->confirmDeliveryByProducer($request->user(), $millingOperation, $request->validated());

        return back()->with('status', 'Delivery confirmed.');
    }

    public function acknowledge(AcknowledgeDiscrepancyRequest $request, MillingOperation $millingOperation): RedirectResponse
    {
        $this->service->acknowledgeDiscrepancy($request->user(), $millingOperation);

        return back()->with('status', 'Discrepancy acknowledged.');
    }
}
