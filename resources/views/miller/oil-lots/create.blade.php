@extends('layouts.admin')
@section('title', 'Create oil lot')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Request #{{ $millRequest->id }}</p>
            <h1 class="display-title text-4xl">Create oil lot</h1>
            <p class="mt-4 text-sm text-stone-500">Record the oil produced from this milling.</p>
        </div>
        <a class="btn-secondary" href="{{ route('mill.mill-requests.show', $millRequest->id) }}">Back</a>
    </div>
    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('mill.oil-lots.store', $millRequest->id) }}" class="space-y-6">
            @csrf
            <x-production-field name="lot_number" label="Lot number" :value="old('lot_number', 'LOT-'.now()->year.'-')" :required="true" maxlength="50" help="Format: LOT-YYYY-NNN (e.g., LOT-2026-001)" />
            <x-production-field name="liters" label="Volume (L)" type="number" :value="old('liters')" :required="true" min="0.01" max="999999.99" step="0.01" />
            <x-production-field name="quality_grade" label="Quality grade" :value="old('quality_grade')" :required="true" :options="collect($qualityGrades)->mapWithKeys(fn($q) => [$q->value => $q->label()])->all()" />
            <x-production-field name="production_date" label="Production date" type="date" :value="old('production_date', now()->toDateString())" :required="true" max="today" />
            <x-production-field name="notes" label="Notes" type="textarea" :value="old('notes')" maxlength="3000" />
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><x-primary-button>Create oil lot</x-primary-button><a class="btn-secondary" href="{{ route('mill.mill-requests.show', $millRequest->id) }}">Cancel</a></div>
        </form>
    </x-card>
@endsection