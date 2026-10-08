<?php

namespace App\Http\Controllers\Miller;

use App\Enums\HarvestStatus;
use App\Enums\MillRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Production\MillRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MillRequestController extends Controller
{
    public function index(): View
    {
        $mill = auth()->user()->mill;
        abort_unless($mill, 403);

        $requests = $mill->millRequests()
            ->with(['harvest.farm.producerProfile.user'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('miller.mill-requests.index', ['requests' => $requests]);
    }

    public function show(MillRequest $mill_request): View
    {
        $this->authorize('view', $mill_request);

        $mill_request->load(['harvest.farm.producerProfile.user', 'oilLot']);

        return view('miller.mill-requests.show', ['request' => $mill_request]);
    }

    public function update(Request $request, MillRequest $mill_request): RedirectResponse
    {
        $this->authorize('update', $mill_request);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                MillRequestStatus::Pending->value,
                MillRequestStatus::Accepted->value,
                MillRequestStatus::Refused->value,
                MillRequestStatus::Completed->value,
            ])],
            'appointment_date' => ['nullable', 'date', 'after_or_equal:today'],
            'response_message' => ['nullable', 'string', 'max:3000'],
        ]);

        $oldStatus = $mill_request->status;

        if ($data['status'] === MillRequestStatus::Completed->value && $oldStatus !== MillRequestStatus::Completed) {
            if ($mill_request->harvest->status !== HarvestStatus::Milled) {
                $mill_request->harvest->status = HarvestStatus::Milled;
                $mill_request->harvest->save();
            }
        }

        $mill_request->update($data);

        if ($data['status'] === MillRequestStatus::Completed->value && ! $mill_request->oilLot) {
            return redirect()->route('mill.oil-lots.create', $mill_request->id)
                ->with('info', 'Milling completed. Now create the oil lot for this request.');
        }

        return back()->with('status', 'Request updated.');
    }
}
