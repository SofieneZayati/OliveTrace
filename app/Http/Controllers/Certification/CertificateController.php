<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use App\Models\Certification\Certificate;

class CertificateController extends Controller
{
    public function show($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with(['certificateRequest.oilLot', 'certificateRequest.labAnalysis'])
            ->firstOrFail();

        return view('certification.certificates.show', compact('certificate'));
    }
}
