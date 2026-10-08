<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-olive-800 leading-tight">
            {{ __('New Certificate Request') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('certification.producer.requests.index') }}" class="text-link inline-flex items-center gap-1"><x-icon name="arrow" class="h-4 w-4 rotate-180" /> Back to Requests</a>
            </div>

            <x-card>
                <h3 class="text-lg font-bold text-olive-800 mb-4 border-b border-olive-100 pb-2">Request Information</h3>
                <form method="POST" action="{{ route('certification.producer.requests.store') }}">
                    @csrf
                    <div class="mb-6">
                        <label for="oil_lot_id" class="block font-medium text-sm text-stone-700 mb-1">Select Oil Lot</label>
                        <select name="oil_lot_id" id="oil_lot_id" class="border-stone-300 focus:border-olive-500 focus:ring-olive-500 rounded-md shadow-sm block w-full bg-white" required>
                            <option value="">-- Select an Oil Lot --</option>
                            @foreach($oilLots as $lot)
                                <option value="{{ $lot->id }}">{{ $lot->lot_number }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-stone-500 mt-2">Only oil lots that do not currently have a pending or approved request are shown.</p>
                        @error('oil_lot_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label for="note" class="block font-medium text-sm text-stone-700 mb-1">Additional Notes</label>
                        <textarea name="note" id="note" rows="4" class="border-stone-300 focus:border-olive-500 focus:ring-olive-500 rounded-md shadow-sm block w-full" placeholder="Optional notes for the laboratory..."></textarea>
                        @error('note') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-olive-100">
                        <button type="submit" class="btn-primary">
                            Submit Request
                        </button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
