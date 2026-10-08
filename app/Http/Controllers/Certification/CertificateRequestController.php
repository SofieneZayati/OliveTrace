<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use App\Models\Certification\CertificateRequest;
use App\Models\Production\OilLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            ->whereDoesntHave('certificateRequests', fn ($query) => $query->whereIn('status', ['pending', 'approved']))
            ->get();

        return view('certification.requests.create', compact('oilLots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'oil_lot_id' => 'required|exists:oil_lots,id',
            'note' => 'nullable|string|max:5000',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Serialize submissions for this lot, including double clicks and concurrent requests.
            $lot = OilLot::whereKey($validated['oil_lot_id'])
                ->where('producer_user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lot->certificateRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
                throw ValidationException::withMessages([
                    'oil_lot_id' => 'This oil lot already has a pending or approved certificate request.',
                ]);
            }

            CertificateRequest::create([
                'oil_lot_id' => $lot->id,
                'producer_user_id' => $request->user()->id,
                'requested_at' => now(),
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
            ]);
        });

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
