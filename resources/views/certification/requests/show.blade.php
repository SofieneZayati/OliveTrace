<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-olive-800 leading-tight">
            {{ __('Request Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('certification.producer.requests.index') }}" class="text-link inline-flex items-center gap-1"><x-icon name="arrow" class="h-4 w-4 rotate-180" /> Back to Requests</a>
            </div>

            <x-card class="mb-6">
                <h3 class="text-lg font-bold text-olive-800 mb-4 border-b border-olive-100 pb-2">Request Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm text-stone-600">
                    <div>
                        <p class="font-semibold text-stone-800">Oil Lot Number</p>
                        <p class="font-mono bg-stone-100 px-2 py-1 rounded inline-block mt-1">{{ $request->oilLot->lot_number }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Requested At</p>
                        <p class="mt-1">{{ $request->requested_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Status</p>
                        <p class="mt-1">
                            @if($request->status === 'approved')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Approved
                                </span>
                            @elseif($request->status === 'rejected')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Rejected
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span> Pending
                                </span>
                            @endif
                        </p>
                    </div>
                    <div class="col-span-2 mt-2">
                        <p class="font-semibold text-stone-800">Notes</p>
                        <p class="mt-1">{{ $request->note ?: 'No additional notes provided.' }}</p>
                    </div>
                </div>
            </x-card>

            @if($request->labAnalysis)
            <x-card class="mb-6">
                <h3 class="text-lg font-bold text-olive-800 mb-4 border-b border-olive-100 pb-2">Laboratory Analysis</h3>
                <div class="grid grid-cols-2 gap-4 text-sm text-stone-600">
                    <div>
                        <p class="font-semibold text-stone-800">Analysis Date</p>
                        <p class="mt-1">{{ $request->labAnalysis->analysis_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Result</p>
                        <p class="mt-1 font-medium {{ $request->labAnalysis->result ? 'text-green-600' : 'text-red-600' }}">{{ $request->labAnalysis->result ? 'Passed' : 'Failed' }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Acidity</p>
                        <p class="mt-1">{{ $request->labAnalysis->acidity }} %</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Peroxide Value</p>
                        <p class="mt-1">{{ $request->labAnalysis->peroxide_value ?? 'N/A' }} meq O2/kg</p>
                    </div>
                    @if($request->labAnalysis->notes)
                        <div class="col-span-2 mt-2">
                            <p class="font-semibold text-stone-800">Lab Notes</p>
                            <p class="mt-1 bg-stone-50 p-3 rounded border border-stone-100 whitespace-pre-wrap">{{ $request->labAnalysis->notes }}</p>
                        </div>
                    @endif
                </div>
            </x-card>
            @endif

            @if($request->certificate)
            <x-card class="border-l-4 border-l-green-500">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-olive-800 mb-2">Certificate Issued</h3>
                        <div class="text-sm text-stone-600 mb-4">
                            <p class="mb-1"><strong>Certificate Number:</strong> <span class="font-mono bg-stone-100 px-1 rounded">{{ $request->certificate->certificate_number }}</span></p>
                            <p class="mb-1"><strong>Type:</strong> {{ $request->certificate->type }}</p>
                            <p><strong>Valid Until:</strong> {{ $request->certificate->expiry_date->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <x-icon name="document" class="h-12 w-12 text-green-200" />
                </div>
                <a href="{{ route('certificates.show', $request->certificate->certificate_number) }}" target="_blank" class="btn-primary inline-flex items-center gap-2">
                    <x-icon name="document" class="h-4 w-4" /> View Public Certificate Page
                </a>
            </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
