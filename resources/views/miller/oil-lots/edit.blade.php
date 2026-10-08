@extends('layouts.admin')
@section('title', 'Edit oil lot')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Lot #{{ $oilLot->lot_number }}</p>
            <h1 class="display-title text-4xl">Edit oil lot</h1>
        </div>
        <a class="btn-secondary" href="{{ route('mill.mill-requests.show', $oilLot->mill_request_id) }}">Back to request</a>
    </div>
    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('mill.oil-lots.update', $oilLot->id) }}" class="space-y-6">
            @csrf @method('PATCH')
            <x-production-field name="lot_number" label="Lot number" :value="old('lot_number', $oilLot->lot_number)" :required="true" maxlength="50" />
            <x-production-field name="liters" label="Volume (L)" type="number" :value="old('liters', $oilLot->liters)" :required="true" min="0.01" max="999999.99" step="0.01" />
            <x-production-field name="quality_grade" label="Quality grade" :value="old('quality_grade', $oilLot->quality_grade->value)" :required="true" :options="collect($qualityGrades)->mapWithKeys(fn($q) => [$q->value => $q->label()])->all()" />
            <x-production-field name="production_date" label="Production date" type="date" :value="old('production_date', $oilLot->production_date->toDateString())" :required="true" max="today" />
            <x-production-field name="notes" label="Notes" type="textarea" :value="old('notes', $oilLot->notes)" maxlength="3000" />
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><x-primary-button>Save oil lot</x-primary-button><a class="btn-secondary" href="{{ route('mill.mill-requests.show', $oilLot->mill_request_id) }}">Cancel</a></div>
        </form>
    </x-card>
@endsection