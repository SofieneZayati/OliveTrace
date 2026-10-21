<x-app-layout title="Analyze certificate request">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-olive-800 leading-tight">
            {{ __('Analyze Certificate Request') }} #{{ $certificateRequest->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('lab.requests.index') }}" class="text-link inline-flex items-center gap-1"><x-icon name="arrow" class="h-4 w-4 rotate-180" /> Back to Requests</a>
            </div>

            <x-card class="mb-6">
                <h3 class="text-lg font-bold text-olive-800 mb-4 border-b border-olive-100 pb-2">Request Details</h3>
                <div class="grid grid-cols-2 gap-4 text-sm text-stone-600">
                    <div>
                        <p class="font-semibold text-stone-800">Producer</p>
                        <p class="mt-1">{{ $certificateRequest->producer->name }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Oil Lot Number</p>
                        <p class="font-mono bg-stone-100 px-2 py-1 rounded inline-block mt-1">{{ $certificateRequest->oilLot->lot_number }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Requested At</p>
                        <p class="mt-1">{{ $certificateRequest->requested_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="col-span-2 mt-2">
                        <p class="font-semibold text-stone-800">Producer Notes</p>
                        <p class="mt-1">{{ $certificateRequest->note ?: 'No notes provided.' }}</p>
                    </div>
                </div>
            </x-card>

            @if($certificateRequest->status === 'pending')
            <x-card>
                <h3 class="text-lg font-bold text-olive-800 mb-4 border-b border-olive-100 pb-2">Record Analysis</h3>
                <form method="POST" action="{{ route('lab.requests.analyze', $certificateRequest) }}" id="analysisForm">
                    @csrf
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="acidity" class="block font-medium text-sm text-stone-700 mb-1">Acidity (%)</label>
                            <input type="number" step="0.01" name="acidity" id="acidity" required class="border-stone-300 focus:border-olive-500 focus:ring-olive-500 rounded-md shadow-sm block w-full" value="{{ old('acidity') }}">
                            @error('acidity') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="peroxide_value" class="block font-medium text-sm text-stone-700 mb-1">Peroxide Value (meq O2/kg)</label>
                            <input type="number" step="0.01" name="peroxide_value" id="peroxide_value" class="border-stone-300 focus:border-olive-500 focus:ring-olive-500 rounded-md shadow-sm block w-full" value="{{ old('peroxide_value') }}">
                            @error('peroxide_value') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="notes" class="block font-medium text-sm text-stone-700 mb-1">Lab Notes</label>
                        <textarea name="notes" id="notes" rows="4" class="border-stone-300 focus:border-olive-500 focus:ring-olive-500 rounded-md shadow-sm block w-full">{{ old('notes') }}</textarea>
                        @error('notes') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6 bg-olive-50 p-5 rounded-xl border border-olive-100">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <h4 class="font-bold text-olive-800 flex items-center gap-2"><x-icon name="sparkles" class="h-5 w-5 text-olive-600" /> AI Assistant</h4>
                            <button type="button" id="btnAiExplain" class="btn-secondary text-xs py-1.5 px-3">Generate Explanation</button>
                        </div>
                        <p class="text-sm text-stone-600 mb-2">Use the AI Assistant to generate a plain-language explanation of these results automatically.</p>
                        <div id="aiResponse" class="lab-ai-report hidden mt-4 rounded-lg border border-olive-100 bg-white p-5" role="status" aria-live="polite"></div>
                        <div id="aiActions" class="hidden mt-3 flex flex-wrap items-center gap-3">
                            <button type="button" id="btnAddAiNotes" class="btn-secondary text-xs py-1.5 px-3">Add to lab notes</button>
                            <span class="text-xs text-stone-500">Review the explanation before adding it to your analysis.</span>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="result" class="block font-medium text-sm text-stone-700 mb-1">Final Decision</label>
                        <select name="result" id="result" class="border-stone-300 focus:border-olive-500 focus:ring-olive-500 rounded-md shadow-sm block w-full bg-white" required>
                            <option value="">-- Select Decision --</option>
                            <option value="1" {{ old('result') == '1' ? 'selected' : '' }}>Approve (Issue Certificate)</option>
                            <option value="0" {{ old('result') == '0' ? 'selected' : '' }}>Reject</option>
                        </select>
                        @error('result') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-olive-100">
                        <button type="submit" class="btn-primary">
                            Record Analysis & Update Request
                        </button>
                    </div>
                </form>
            </x-card>

            <script>
                document.getElementById('btnAddAiNotes').addEventListener('click', function() {
                    const notesField = document.getElementById('notes');
                    const explanation = document.getElementById('aiResponse').innerText.trim();
                    notesField.value += (notesField.value ? '\n\n' : '') + 'AI Note:\n' + explanation;
                    notesField.style.height = 'auto';
                    notesField.style.height = Math.min(notesField.scrollHeight, 400) + 'px';
                    this.disabled = true;
                    this.textContent = 'Added to lab notes';
                });

                document.getElementById('btnAiExplain').addEventListener('click', async function() {
                    const acidity = document.getElementById('acidity').value;
                    const peroxide = document.getElementById('peroxide_value').value;
                    const notes = document.getElementById('notes').value;

                    if (!acidity) {
                        alert('Please fill the Acidity field first.');
                        return;
                    }

                    const button = this;
                    const aiDiv = document.getElementById('aiResponse');
                    const aiActions = document.getElementById('aiActions');
                    const addButton = document.getElementById('btnAddAiNotes');
                    aiActions.classList.add('hidden');
                    aiDiv.classList.remove('hidden');
                    aiDiv.classList.remove('text-red-500');
                    aiDiv.textContent = 'Generating AI explanation...';
                    button.disabled = true;

                    try {
                        const response = await fetch("{{ route('lab.requests.ai-explanation') }}", {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                acidity: acidity,
                                peroxide_value: peroxide,
                                notes: notes
                            })
                        });
                        const data = await response.json();

                        if (!response.ok || typeof data.explanation !== 'string') {
                            throw new Error(data.message || 'The AI Assistant could not generate an explanation.');
                        }

                        if (typeof data.explanation_html === 'string') {
                            // The controller strips raw HTML and unsafe links from Markdown.
                            aiDiv.innerHTML = data.explanation_html;
                        } else {
                            aiDiv.textContent = data.explanation;
                        }
                        if (!/^(AI Assistant|Unable to parse)/.test(data.explanation)) {
                            aiActions.classList.remove('hidden');
                            addButton.disabled = false;
                            addButton.textContent = 'Add to lab notes';
                        }
                    } catch (error) {
                        console.error('AI explanation request failed:', error);
                        aiDiv.textContent = error.message || 'Failed to connect to AI Assistant.';
                        aiDiv.classList.add('text-red-500');
                    } finally {
                        button.disabled = false;
                    }
                });
            </script>
            @else
            <x-card class="mb-6">
                <div class="flex items-center justify-between border-b border-olive-100 pb-3 mb-4">
                    <h3 class="text-lg font-bold text-olive-800">Recorded Laboratory Analysis</h3>
                    <a href="{{ route('lab.requests.edit', $certificateRequest) }}" class="btn-primary text-xs py-1.5 px-3 inline-flex items-center gap-1.5">
                        Modify Request
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm text-stone-600">
                    <div>
                        <p class="font-semibold text-stone-800">Analysis Date</p>
                        <p class="mt-1">{{ $certificateRequest->labAnalysis?->analysis_date?->format('M d, Y') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Decision / Status</p>
                        <p class="mt-1 font-medium">
                            @if($certificateRequest->status === 'approved')
                                <span class="inline-flex items-center gap-1 text-green-700 font-semibold">
                                    <x-icon name="check" class="h-4 w-4 text-green-600" /> Approved
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-red-600 font-semibold">
                                    Rejected
                                </span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Acidity</p>
                        <p class="mt-1">{{ $certificateRequest->labAnalysis?->acidity ?? 'N/A' }} %</p>
                    </div>
                    <div>
                        <p class="font-semibold text-stone-800">Peroxide Value</p>
                        <p class="mt-1">{{ $certificateRequest->labAnalysis?->peroxide_value !== null ? $certificateRequest->labAnalysis->peroxide_value . ' meq O2/kg' : 'N/A' }}</p>
                    </div>
                    @if($certificateRequest->labAnalysis?->notes)
                        <div class="col-span-2 mt-2">
                            <p class="font-semibold text-stone-800">Lab Notes</p>
                            <p class="mt-1 bg-stone-50 p-3 rounded border border-stone-100 whitespace-pre-wrap">{{ $certificateRequest->labAnalysis->notes }}</p>
                        </div>
                    @endif
                </div>
            </x-card>

            @if($certificateRequest->certificate)
            <x-card class="border-l-4 border-l-green-500 mb-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-olive-800 mb-2">Certificate Issued</h3>
                        <div class="text-sm text-stone-600 mb-4">
                            <p class="mb-1"><strong>Certificate Number:</strong> <span class="font-mono bg-stone-100 px-1 rounded">{{ $certificateRequest->certificate->certificate_number }}</span></p>
                            <p class="mb-1"><strong>Type:</strong> {{ $certificateRequest->certificate->type }}</p>
                            <p class="mb-1"><strong>Status:</strong> <span class="font-semibold {{ $certificateRequest->certificate->isCurrentlyValid() ? 'text-green-700' : 'text-red-600' }}">{{ strtoupper($certificateRequest->certificate->status) }}</span></p>
                            <p><strong>Valid Until:</strong> {{ $certificateRequest->certificate->expiry_date?->format('M d, Y') ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <x-icon name="document" class="h-12 w-12 text-green-200" />
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('certificates.show', $certificateRequest->certificate->certificate_number) }}" target="_blank" class="btn-primary inline-flex items-center gap-2 text-xs py-2 px-3">
                        <x-icon name="document" class="h-4 w-4" /> View Public Certificate
                    </a>
                    <a href="{{ route('certificates.pdf', $certificateRequest->certificate->certificate_number) }}" class="btn-secondary inline-flex items-center gap-2 text-xs py-2 px-3">
                        Download PDF
                    </a>
                </div>
            </x-card>
            @endif

            <div class="flex items-center justify-between">
                <a href="{{ route('lab.requests.index') }}" class="btn-secondary inline-flex">Back to list</a>
                <a href="{{ route('lab.requests.edit', $certificateRequest) }}" class="btn-primary inline-flex items-center gap-1.5">
                    Modify Request
                </a>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
