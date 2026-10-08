<?php

namespace App\Http\Controllers\Miller;

use App\Enums\MillRequestStatus;
use App\Enums\OilQuality;
use App\Http\Controllers\Controller;
use App\Models\Production\MillRequest;
use App\Models\Production\OilLot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OilLotController extends Controller
{
    public function create(MillRequest $mill_request): View|RedirectResponse
    {
        $this->authorize('create', OilLot::class);
        if ($mill_request->mill?->user_id !== auth()->id()) {
            abort(403);
        }
        if ($mill_request->status !== MillRequestStatus::Completed) {
            abort(403, 'Oil lot can only be created for completed milling requests.');
        }
        if ($mill_request->oilLot) {
            return redirect()->route('mill.oil-lots.edit', $mill_request->oilLot);
        }

        return view('miller.oil-lots.create', [
            'millRequest' => $mill_request,
            'qualityGrades' => OilQuality::cases(),
        ]);
    }

    public function store(Request $request, MillRequest $mill_request): RedirectResponse
    {
        $this->authorize('create', OilLot::class);
        if ($mill_request->mill?->user_id !== auth()->id()) {
            abort(403);
        }
        if ($mill_request->status !== MillRequestStatus::Completed) {
            throw ValidationException::withMessages(['mill_request' => 'Oil lot can only be created for completed milling requests.']);
        }
        if ($mill_request->oilLot) {
            throw ValidationException::withMessages(['mill_request' => 'Oil lot already exists for this request.']);
        }

        $data = $request->validate([
            'lot_number' => ['required', 'string', 'max:50', 'unique:oil_lots,lot_number'],
            'liters' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999.99'],
            'quality_grade' => ['required', Rule::enum(OilQuality::class)],
            'production_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $oilLot = new OilLot($data);
        $oilLot->mill_request_id = $mill_request->id;
        $oilLot->save();

        return redirect()->route('mill.mill-requests.show', $mill_request->id)
            ->with('status', 'Oil lot created successfully.');
    }

    public function edit(OilLot $oilLot): View
    {
        $this->authorize('update', $oilLot);

        return view('miller.oil-lots.edit', [
            'oilLot' => $oilLot,
            'qualityGrades' => OilQuality::cases(),
        ]);
    }

    public function update(Request $request, OilLot $oilLot): RedirectResponse
    {
        $this->authorize('update', $oilLot);

        $data = $request->validate([
            'lot_number' => ['required', 'string', 'max:50', 'unique:oil_lots,lot_number,'.$oilLot->id],
            'liters' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999.99'],
            'quality_grade' => ['required', Rule::enum(OilQuality::class)],
            'production_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $oilLot->update($data);

        return redirect()->route('mill.mill-requests.show', $oilLot->mill_request_id)
            ->with('status', 'Oil lot updated.');
    }
}
