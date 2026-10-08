<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CertificateRequestController extends Controller
{
    public function index()
    {
        $requests = \App\Models\Certification\CertificateRequest::where('producer_user_id', auth()->id())
            ->with(['oilLot', 'certificate'])
            ->latest()
            ->get();
        return view('certification.requests.index', compact('requests'));
    }

    public function create()
    {
        // Producer selects one of their OilLots
        $oilLots = \App\Models\Production\OilLot::where('producer_user_id', auth()->id())
            ->doesntHave('certificateRequests')
            ->get();

        // Temporary testing fallback: create a dummy oil lot if none are available
        if ($oilLots->isEmpty()) {
            $dummy = \App\Models\Production\OilLot::create([
                'producer_user_id' => auth()->id(),
                'lot_number' => 'LOT-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5)),
            ]);
            $oilLots->push($dummy);
        }

        return view('certification.requests.create', compact('oilLots'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'oil_lot_id' => 'required|exists:oil_lots,id',
            'note' => 'nullable|string',
        ]);

        // Ensure lot belongs to the user
        $lot = \App\Models\Production\OilLot::where('id', $validated['oil_lot_id'])
            ->where('producer_user_id', auth()->id())
            ->firstOrFail();

        \App\Models\Certification\CertificateRequest::create([
            'oil_lot_id' => $lot->id,
            'producer_user_id' => auth()->id(),
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('certification.producer.requests.index')
            ->with('success', 'Certificate request submitted successfully.');
    }

    public function show(\App\Models\Certification\CertificateRequest $request)
    {
        if ($request->producer_user_id !== auth()->id()) {
            abort(403);
        }
        $request->load(['oilLot', 'labAnalysis', 'certificate']);
        return view('certification.requests.show', compact('request'));
    }
}
