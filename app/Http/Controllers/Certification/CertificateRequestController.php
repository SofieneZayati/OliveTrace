<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use App\Models\Certification\CertificateRequest;
use App\Models\Production\OilLot;
use Illuminate\Http\Request;

class CertificateRequestController extends Controller
{
    public function index()
    {
        $requests = CertificateRequest::where('producer_user_id', auth()->id())
            ->with(['oilLot', 'certificate'])
            ->latest()
            ->get();

        return view('certification.requests.index', compact('requests'));
    }

    public function create()
    {
        // Producer selects one of their OilLots
        $oilLots = OilLot::where('producer_user_id', auth()->id())
            ->doesntHave('certificateRequests')
            ->get();

        return view('certification.requests.create', compact('oilLots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'oil_lot_id' => 'required|exists:oil_lots,id',
            'note' => 'nullable|string',
        ]);

        // Ensure lot belongs to the user
        $lot = OilLot::where('id', $validated['oil_lot_id'])
            ->where('producer_user_id', auth()->id())
            ->firstOrFail();

        CertificateRequest::create([
            'oil_lot_id' => $lot->id,
            'producer_user_id' => auth()->id(),
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('certification.producer.requests.index')
            ->with('success', 'Certificate request submitted successfully.');
    }

    public function show(CertificateRequest $request)
    {
        if ($request->producer_user_id !== auth()->id()) {
            abort(403);
        }
        $request->load(['oilLot', 'labAnalysis', 'certificate']);

        return view('certification.requests.show', compact('request'));
    }
}
