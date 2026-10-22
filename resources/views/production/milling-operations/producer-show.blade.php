@extends('layouts.admin')

@section('title', 'Milling Operation')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Milling Operation #{{ $millingOperation->id }}</h1>
        <a href="{{ route('producer.milling-operations.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Back to list</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Details</h2>
            <dl class="space-y-2 text-sm text-gray-700">
                <div class="flex justify-between"><dt>Mill</dt><dd>{{ $millingOperation->millRequest->targetLabel() }}</dd></div>
                <div class="flex justify-between"><dt>Requested Qty</dt><dd>{{ $millingOperation->millRequest->quantity_kg }} kg</dd></div>
                <div class="flex justify-between"><dt>Status</dt><dd>{{ $millingOperation->status->label() }}</dd></div>
            </dl>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Delivery</h2>
            <dl class="space-y-2 text-sm text-gray-700">
                <div class="flex justify-between"><dt>Confirmed</dt><dd>{{ $millingOperation->producer_confirmed_at ? $millingOperation->producer_confirmed_at : '-' }}</dd></div>
                <div class="flex justify-between"><dt>Received Qty</dt><dd>{{ $millingOperation->received_quantity_kg ? $millingOperation->received_quantity_kg.' kg' : '-' }}</dd></div>
                <div class="flex justify-between"><dt>Discrepancy</dt><dd>{{ $millingOperation->discrepancy_percent ? $millingOperation->discrepancy_percent.'%' : '-' }}</dd></div>
                <div class="flex justify-between"><dt>Note</dt><dd>{{ $millingOperation->discrepancy_note ?? '-' }}</dd></div>
            </dl>

            @if($millingOperation->status->value === 'scheduled' && ! $millingOperation->producer_confirmed_at)
                <form method="POST" action="{{ route('producer.milling-operations.confirm-delivery', $millingOperation) }}" class="mt-4">
                    @csrf
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Confirm Delivery</button>
                </form>
            @endif

            @if($millingOperation->miller_confirmed_at && (float)$millingOperation->discrepancy_percent > 10.0 && ! $millingOperation->discrepancy_acknowledged_at)
                <form method="POST" action="{{ route('producer.milling-operations.acknowledge', $millingOperation) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="acknowledge" value="1">
                    <button class="px-4 py-2 bg-yellow-600 text-white rounded-md">Acknowledge Discrepancy (>10%)</button>
                </form>
            @endif
        </div>

        @if($millingOperation->oilLot)
        <div class="bg-white shadow-sm rounded-lg p-6 lg:col-span-2">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Oil Lot Created</h2>
            <dl class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-700">
                <div><dt class="font-medium">Lot Number</dt><dd>{{ $millingOperation->oilLot->lot_number }}</dd></div>
                <div><dt class="font-medium">Liters</dt><dd>{{ $millingOperation->oilLot->liters }}</dd></div>
                <div><dt class="font-medium">Quality</dt><dd>{{ $millingOperation->oilLot->quality_grade?->label() }}</dd></div>
                <div><dt class="font-medium">Extraction Rate</dt><dd>{{ $millingOperation->extraction_rate }}%</dd></div>
            </dl>
        </div>
        @endif
    </div>
</div>
@endsection
