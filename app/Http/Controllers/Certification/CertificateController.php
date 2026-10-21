<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use App\Models\Certification\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function show($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with(['certificateRequest.oilLot', 'certificateRequest.producer', 'certificateRequest.labAnalysis.labUser'])
            ->firstOrFail();

        $valid = $certificate->isCurrentlyValid();

        return view('certification.certificates.show', compact('certificate', 'valid'));
    }

    public function downloadPdf($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with(['certificateRequest.oilLot', 'certificateRequest.producer', 'certificateRequest.labAnalysis.labUser'])
            ->firstOrFail();

        $valid = $certificate->isCurrentlyValid();

        $pdf = Pdf::loadView('certification.certificates.pdf', compact('certificate', 'valid'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("Certificate-{$certificate->certificate_number}.pdf");
    }
}
