<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use App\Models\Certification\Certificate;

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
}
