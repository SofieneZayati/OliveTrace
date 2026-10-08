<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LabAnalysisController extends Controller
{
    public function index()
    {
        $requests = \App\Models\Certification\CertificateRequest::with('oilLot')
            ->latest('requested_at')
            ->get();
        return view('certification.lab.index', compact('requests'));
    }

    public function show(\App\Models\Certification\CertificateRequest $certificateRequest)
    {
        $certificateRequest->load(['oilLot', 'labAnalysis', 'certificate']);
        return view('certification.lab.show', compact('certificateRequest'));
    }

    public function analyze(\Illuminate\Http\Request $request, \App\Models\Certification\CertificateRequest $certificateRequest)
    {
        if ($certificateRequest->status !== 'pending') {
            return back()->with('error', 'This request is already processed.');
        }

        $validated = $request->validate([
            'acidity' => 'required|numeric|min:0',
            'peroxide_value' => 'nullable|numeric|min:0',
            'result' => 'required|boolean', // 1 for approve, 0 for reject
            'notes' => 'nullable|string',
            // If approved, we can ask for some cert details, or auto-generate
        ]);

        \DB::transaction(function () use ($validated, $certificateRequest) {
            $analysis = \App\Models\Certification\LabAnalysis::create([
                'certificate_request_id' => $certificateRequest->id,
                'lab_user_id' => auth()->id(),
                'analysis_date' => now(),
                'acidity' => $validated['acidity'],
                'peroxide_value' => $validated['peroxide_value'],
                'result' => $validated['result'],
                'notes' => $validated['notes'],
            ]);

            if ($validated['result']) {
                $certificateRequest->update(['status' => 'approved']);
                
                \App\Models\Certification\Certificate::create([
                    'certificate_request_id' => $certificateRequest->id,
                    'certificate_number' => 'CERT-' . strtoupper(uniqid()),
                    'type' => 'Extra Virgin Olive Oil', // hardcoded or input
                    'issue_date' => now(),
                    'expiry_date' => now()->addYear(),
                    'status' => 'active',
                ]);
            } else {
                $certificateRequest->update(['status' => 'rejected']);
            }
        });

        return redirect()->route('lab.requests.index')->with('success', 'Analysis recorded and request updated.');
    }

    public function aiExplanation(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'acidity' => 'required|numeric',
            'peroxide_value' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $explanation = \App\Services\LabResultAIAssistant::generateExplanation(
            $validated['acidity'],
            $validated['peroxide_value'] ?? null,
            $validated['notes'] ?? ''
        );

        return response()->json(['explanation' => $explanation]);
    }
}
