@extends('layouts.admin')

@section('title', 'Milling Operation')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Milling Operation #{{ $millingOperation->id }}</h1>
        <a href="{{ route('miller.milling-operations.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Back to list</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Request Details</h2>
            <dl class="space-y-2 text-sm text-gray-700">
                <div class="flex justify-between"><dt>Harvest</dt><dd>{{ $millingOperation->millRequest->harvest->harvest_date }}</dd></div>
                <div class="flex justify-between"><dt>Requested Qty</dt><dd>{{ $millingOperation->millRequest->quantity_kg }} kg</dd></div>
                <div class="flex justify-between"><dt>Producer</dt><dd>{{ $millingOperation->millRequest->harvest->farm->producerProfile->user->name ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt>Status</dt><dd>{{ $millingOperation->status->label() }}</dd></div>
            </dl>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Delivery & Reception</h2>
            <dl class="space-y-2 text-sm text-gray-700">
                <div class="flex justify-between"><dt>Producer confirmed</dt><dd>{{ $millingOperation->producer_confirmed_at ? $millingOperation->producer_confirmed_at : '-' }}</dd></div>
                <div class="flex justify-between"><dt>Received Qty</dt><dd>{{ $millingOperation->received_quantity_kg ? $millingOperation->received_quantity_kg.' kg' : '-' }}</dd></div>
                <div class="flex justify-between"><dt>Condition</dt><dd>{{ $millingOperation->olive_condition?->label() ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt>Discrepancy</dt><dd>{{ $millingOperation->discrepancy_percent ? $millingOperation->discrepancy_percent.'%' : '-' }}</dd></div>
                <div class="flex justify-between"><dt>Note</dt><dd>{{ $millingOperation->discrepancy_note ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt>Acknowledged</dt><dd>{{ $millingOperation->discrepancy_acknowledged_at ? 'Yes' : '-' }}</dd></div>
            </dl>

            @if($millingOperation->status->value === 'scheduled' && ! $millingOperation->miller_confirmed_at && $millingOperation->producer_confirmed_at)
                <form method="POST" action="{{ route('miller.milling-operations.confirm-reception', $millingOperation) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="received_quantity_kg" class="field-label-with-help">Received quantity (kg) <span class="required-mark" aria-hidden="true">*</span> <span class="field-help" tabindex="0" role="img" aria-label="Enter the quantity physically received by the mill. The system compares it with the requested quantity."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Enter the quantity physically received by the mill. The system compares it with the requested quantity.</span></span></label>
                        <input id="received_quantity_kg" type="number" min="0.01" max="9999999999.99" step="0.01" name="received_quantity_kg" value="{{ old('received_quantity_kg', $millingOperation->millRequest->quantity_kg) }}" required aria-required="true" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label for="olive_condition" class="field-label-with-help">Olive condition <span class="required-mark" aria-hidden="true">*</span> <span class="field-help" tabindex="0" role="img" aria-label="Record the condition of the olives when they arrive at the mill."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Record the condition of the olives when they arrive at the mill.</span></span></label>
                        <select id="olive_condition" name="olive_condition" required aria-required="true" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="good" @selected(old('olive_condition', 'good') === 'good')>Good</option>
                            <option value="acceptable" @selected(old('olive_condition') === 'acceptable')>Acceptable</option>
                            <option value="poor" @selected(old('olive_condition') === 'poor')>Poor</option>
                        </select>
                    </div>
                    <div>
                        <label for="discrepancy_note" class="field-label-with-help">Discrepancy note (if >=5%) <span class="field-help" tabindex="0" role="img" aria-label="Explain the difference when the received quantity differs by 5% or more."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Explain the difference when the received quantity differs by 5% or more.</span></span></label>
                        <textarea id="discrepancy_note" name="discrepancy_note" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('discrepancy_note') }}</textarea>
                    </div>
                    <div>
                        <label for="delivery_notes" class="field-label-with-help">Delivery notes <span class="field-help" tabindex="0" role="img" aria-label="Add useful details about transport, packaging, weighing, or delivery conditions."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Add useful details about transport, packaging, weighing, or delivery conditions.</span></span></label>
                        <textarea id="delivery_notes" name="delivery_notes" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('delivery_notes') }}</textarea>
                    </div>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Confirm Reception</button>
                </form>
            @endif

            @if($millingOperation->status->value === 'scheduled' && $millingOperation->producer_confirmed_at && $millingOperation->miller_confirmed_at && ((float)$millingOperation->discrepancy_percent <= 10.0 || $millingOperation->discrepancy_acknowledged_at))
                <form method="POST" action="{{ route('miller.milling-operations.start', $millingOperation) }}" class="mt-4">
                    @csrf
                    <button class="px-4 py-2 bg-green-600 text-white rounded-md">Start Milling</button>
                </form>
            @endif
        </div>

        @if($millingOperation->status->value === 'in_progress')
        <div class="bg-white shadow-sm rounded-lg p-6 lg:col-span-2">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Complete Milling</h2>
            <form method="POST" action="{{ route('miller.milling-operations.complete', $millingOperation) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label for="processed_quantity_kg" class="field-label-with-help">Processed quantity (kg) <span class="required-mark" aria-hidden="true">*</span> <span class="field-help" tabindex="0" role="img" aria-label="Enter the amount of olives actually processed. It cannot exceed the received quantity."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Enter the amount of olives actually processed. It cannot exceed the received quantity.</span></span></label>
                    <input id="processed_quantity_kg" type="number" min="0.01" max="9999999999.99" step="0.01" name="processed_quantity_kg" value="{{ old('processed_quantity_kg', $millingOperation->received_quantity_kg) }}" required aria-required="true" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="output_volume_l" class="field-label-with-help">Output volume (L) <span class="required-mark" aria-hidden="true">*</span> <span class="field-help" tabindex="0" role="img" aria-label="Enter the litres of olive oil produced. This is used to calculate the extraction rate."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Enter the litres of olive oil produced. This is used to calculate the extraction rate.</span></span></label>
                    <input id="output_volume_l" type="number" min="0.01" max="9999999999.99" step="0.01" name="output_volume_l" value="{{ old('output_volume_l') }}" required aria-required="true" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="pomace_quantity_kg" class="field-label-with-help">Pomace quantity (kg) <span class="field-help" tabindex="0" role="img" aria-label="Enter the approximate solid olive residue remaining after extraction."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Enter the approximate solid olive residue remaining after extraction.</span></span></label>
                    <input id="pomace_quantity_kg" type="number" min="0" max="9999999999.99" step="0.01" name="pomace_quantity_kg" value="{{ old('pomace_quantity_kg') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="quality_grade" class="field-label-with-help">Oil quality grade <span class="required-mark" aria-hidden="true">*</span> <span class="field-help" tabindex="0" role="img" aria-label="Select the quality classification assigned to the produced oil lot."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Select the quality classification assigned to the produced oil lot.</span></span></label>
                    <select id="quality_grade" name="quality_grade" required aria-required="true" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="extra_virgin" @selected(old('quality_grade', 'extra_virgin') === 'extra_virgin')>Extra Virgin</option>
                        <option value="virgin" @selected(old('quality_grade') === 'virgin')>Virgin</option>
                        <option value="lampante" @selected(old('quality_grade') === 'lampante')>Lampante</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="lot_notes" class="field-label-with-help">Lot notes <span class="field-help" tabindex="0" role="img" aria-label="Add notes that belong to the resulting oil lot and may help with traceability."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Add notes that belong to the resulting oil lot and may help with traceability.</span></span></label>
                    <textarea id="lot_notes" name="lot_notes" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('lot_notes') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label for="notes" class="field-label-with-help">Operation notes <span class="field-help" tabindex="0" role="img" aria-label="Add internal notes about the milling process, equipment, or exceptional events."><x-icon name="info" class="field-help-icon" /><span class="field-help-tooltip" role="tooltip">Add internal notes about the milling process, equipment, or exceptional events.</span></span></label>
                    <textarea id="notes" name="notes" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Complete & Create Lot</button>
                </div>
            </form>
        </div>
        @endif

        @if($millingOperation->oilLot)
        <div class="bg-white shadow-sm rounded-lg p-6 lg:col-span-2">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Oil Lot</h2>
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700">
                <div><dt class="font-medium">Lot Number</dt><dd>{{ $millingOperation->oilLot->lot_number }}</dd></div>
                <div><dt class="font-medium">Liters</dt><dd>{{ $millingOperation->oilLot->liters }}</dd></div>
                <div><dt class="font-medium">Quality</dt><dd>{{ $millingOperation->oilLot->quality_grade?->label() }}</dd></div>
                <div><dt class="font-medium">Extraction Rate</dt><dd>{{ $millingOperation->extraction_rate }}%</dd></div>
                <div><dt class="font-medium">Processed</dt><dd>{{ $millingOperation->processed_quantity_kg }} kg</dd></div>
                <div><dt class="font-medium">Production Date</dt><dd>{{ $millingOperation->oilLot->production_date }}</dd></div>
            </dl>
        </div>
        @endif
    </div>
</div>
@endsection
