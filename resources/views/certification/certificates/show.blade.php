<x-guest-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg border-t-8 {{ $certificate->status === 'active' && $certificate->expiry_date->isFuture() ? 'border-green-500' : 'border-red-500' }}">
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
                            @if($certificate->status === 'active' && $certificate->expiry_date->isFuture())
                                <span class="bg-green-100 text-green-800 font-bold px-3 py-1 rounded-full text-sm">VALID</span>
                            @else
                                <span class="bg-red-100 text-red-800 font-bold px-3 py-1 rounded-full text-sm">EXPIRED / REVOKED</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h4 class="font-bold text-gray-700 border-b pb-2 mb-2">Product Information</h4>
                            <p><strong>Producer:</strong> {{ $certificate->certificateRequest->producer->name }}</p>
                            <p><strong>Oil Lot Number:</strong> {{ $certificate->certificateRequest->oilLot->lot_number }}</p>
                            <p><strong>Product Type:</strong> {{ $certificate->type }}</p>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-700 border-b pb-2 mb-2">Validity</h4>
                            <p><strong>Issue Date:</strong> {{ $certificate->issue_date->format('F d, Y') }}</p>
                            <p><strong>Valid Until:</strong> {{ $certificate->expiry_date->format('F d, Y') }}</p>
                            <p><strong>Issuing Laboratory:</strong> {{ $certificate->certificateRequest->labAnalysis->labUser->name }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-gray-700 border-b pb-2 mb-2">Laboratory Results</h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p><strong>Analysis Date:</strong> {{ $certificate->certificateRequest->labAnalysis->analysis_date->format('F d, Y') }}</p>
                            <p><strong>Acidity:</strong> {{ $certificate->certificateRequest->labAnalysis->acidity }} %</p>
                            @if($certificate->certificateRequest->labAnalysis->peroxide_value)
                                <p><strong>Peroxide Value:</strong> {{ $certificate->certificateRequest->labAnalysis->peroxide_value }} meq O2/kg</p>
                            @endif
                            <p><strong>Conclusion:</strong> <span class="text-green-600 font-bold">Approved for Certification</span></p>
                        </div>
                    </div>
                    
                    <div class="mt-8 text-center text-sm text-gray-500 border-t pt-4">
                        This document is digitally verifiable via the OliveTrace platform.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
