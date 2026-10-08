<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use App\Models\Certification\Certificate;
use App\Models\Certification\CertificateRequest;
use App\Models\Certification\LabAnalysis;
use App\Services\LabResultAIAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LabAnalysisController extends Controller
{
    public function index()
    {
        $requests = CertificateRequest::with('oilLot')
            ->latest('requested_at')
            ->get();

        return view('certification.lab.index', compact('requests'));
    }

    public function show(CertificateRequest $certificateRequest)
    {
        $certificateRequest->load(['oilLot', 'labAnalysis', 'certificate']);

        return view('certification.lab.show', compact('certificateRequest'));
    }

    public function analyze(Request $request, CertificateRequest $certificateRequest)
    {
        if ($certificateRequest->status !== 'pending') {
            return back()->with('error', 'This request is already processed.');
        }

        $validated = $request->validate([
            'acidity' => 'required|numeric|min:0',
            'peroxide_value' => 'nullable|numeric|min:0',
            'result' => 'required|boolean', // 1 for approve, 0 for reject
            'notes' => 'nullable|string|max:5000',
        ]);

        $processed = DB::transaction(function () use ($validated, $certificateRequest, $request) {
            // The route-bound model can be stale by the time another laboratory submits.
            $certificateRequest = CertificateRequest::whereKey($certificateRequest->id)->lockForUpdate()->firstOrFail();
            if ($certificateRequest->status !== 'pending') {
                return false;
            }

            LabAnalysis::create([
                'certificate_request_id' => $certificateRequest->id,
                'lab_user_id' => $request->user()->id,
                'analysis_date' => now(),
                'acidity' => $validated['acidity'],
                'peroxide_value' => $validated['peroxide_value'] ?? null,
                'result' => $validated['result'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($validated['result']) {
                $certificateRequest->update(['status' => 'approved']);

                Certificate::create([
                    'certificate_request_id' => $certificateRequest->id,
                    'certificate_number' => 'CERT-'.strtoupper(uniqid()),
                    'type' => ($certificateRequest->oilLot?->quality_grade?->label() ?? 'Unspecified').' Olive Oil',
                    'issue_date' => now(),
                    'expiry_date' => now()->addYear(),
                    'status' => 'active',
                ]);
            } else {
                $certificateRequest->update(['status' => 'rejected']);
            }

            return true;
        });

        if (! $processed) {
            return back()->with('error', 'This request is already processed.');
        }

        return redirect()->route('lab.requests.index')->with('success', 'Analysis recorded and request updated.');
    }

    public function aiExplanation(Request $request)
    {
        $validated = $request->validate([
            'acidity' => 'required|numeric|min:0',
            'peroxide_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $explanation = LabResultAIAssistant::generateExplanation(
            $validated['acidity'],
            $validated['peroxide_value'] ?? null,
            $validated['notes'] ?? ''
        );

        return response()->json([
            'explanation' => $explanation,
            'explanation_html' => Str::markdown($explanation, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]),
        ]);
    }
}
