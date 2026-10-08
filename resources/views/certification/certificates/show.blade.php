@extends('layouts.front')
@section('title', 'Certificate verification')
@section('content')
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg border-t-8 {{ $valid ? 'border-green-500' : 'border-red-500' }}">
                <div class="p-8 text-center border-b">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Official Certificate of Analysis</h2>
                    <p class="text-gray-500">OliveTrace Verification System</p>
                </div>
                
                <div class="p-8 text-gray-900">
                    <div class="flex justify-between items-center mb-8 border-b pb-4">
                        <div>
                            <p class="text-sm text-gray-500">Certificate No.</p>
                            <p class="font-bold text-lg">{{ $certificate->certificate_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Status</p>
                            @if($valid)
                                <span class="bg-green-100 text-green-800 font-bold px-3 py-1 rounded-full text-sm">VALID</span>
                            @else
                                <span class="bg-red-100 text-red-800 font-bold px-3 py-1 rounded-full text-sm">NOT VALID</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-8 mb-8 sm:grid-cols-2">
                        <div>
                            <h4 class="font-bold text-gray-700 border-b pb-2 mb-2">Product Information</h4>
                            <p><strong>Producer:</strong> {{ $certificate->certificateRequest?->producer?->name ?? 'Not available' }}</p>
                            <p><strong>Oil Lot Number:</strong> {{ $certificate->certificateRequest?->oilLot?->lot_number ?? 'Not available' }}</p>
                            <p><strong>Product Type:</strong> {{ \App\Enums\OilQuality::tryFrom($certificate->type)?->label() ?? $certificate->type }}</p>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-700 border-b pb-2 mb-2">Validity</h4>
                            <p><strong>Issue Date:</strong> {{ $certificate->issue_date?->format('F d, Y') ?? 'Not available' }}</p>
                            <p><strong>Valid Until:</strong> {{ $certificate->expiry_date?->format('F d, Y') ?? 'Not available' }}</p>
                            <p><strong>Issuing Laboratory:</strong> {{ $certificate->certificateRequest?->labAnalysis?->labUser?->name ?? 'Not available' }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-gray-700 border-b pb-2 mb-2">Laboratory Results</h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            @if($analysis = $certificate->certificateRequest?->labAnalysis)
                                <p><strong>Analysis Date:</strong> {{ $analysis->analysis_date?->format('F d, Y') ?? 'Not available' }}</p>
                                <p><strong>Acidity:</strong> {{ $analysis->acidity }} %</p>
                                @if($analysis->peroxide_value !== null)
                                    <p><strong>Peroxide Value:</strong> {{ $analysis->peroxide_value }} meq O2/kg</p>
                                @endif
                                <p><strong>Laboratory decision:</strong> {{ $analysis->result ? 'Approved' : 'Rejected' }}</p>
                            @else
                                <p>Laboratory results are not available.</p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-8 text-center text-sm text-gray-500 border-t pt-4">
                        This document is digitally verifiable via the OliveTrace platform.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
