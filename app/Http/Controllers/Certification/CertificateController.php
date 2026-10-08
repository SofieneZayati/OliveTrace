<?php

namespace App\Http\Controllers\Certification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function show($certificateNumber)
    {
        $certificate = \App\Models\Certification\Certificate::where('certificate_number', $certificateNumber)
            ->with(['certificateRequest.oilLot', 'certificateRequest.labAnalysis'])
            ->firstOrFail();

        return view('certification.certificates.show', compact('certificate'));
    }
}
