@extends('layouts.admin')
@section('title', $harvest ? 'Edit harvest' : 'Declare a harvest')
@section('content')
    <div class="mb-8 max-w-2xl"><p class="eyebrow mb-4 text-olive-600">Record the harvest</p><h1 class="display-title text-4xl">{{ $harvest ? 'Keep your harvest accurate.' : 'The fruit of your land.' }}</h1><p class="mt-4 text-sm leading-6 text-stone-500">Declare what was picked, when it ends and how it was harvested. Fields marked * are required.</p></div>
    <x-card class="max-w-4xl">
        <form method="POST" action="{{ $harvest ? route('producer.harvests.update', $harvest->id) : route('producer.harvests.store') }}" class="space-y-7">
            @csrf @if($harvest) @method('PATCH') @endif
            <div class="grid gap-6 sm:grid-cols-2">
                <x-production-field name="farm_id" label="Farm" :value="$harvest?->farm_id ?? $presetFarmId" :required="true" :options="collect($farms)->mapWithKeys(fn($farm) => [$farm->id => $farm->name.' — '.$farm->governorate])->all()" help="Only your active farms are listed." />
                <x-production-field name="harvest_date" label="Harvest date" type="date" :value="$harvest?->harvest_date?->toDateString()" :required="true" max="today" />
                <x-production-field name="expected_end_date" label="Expected end of harvest" type="date" :value="$harvest?->expected_end_date?->toDateString()" help="Lets you schedule a mill while the harvest is still running." />
                <x-production-field name="method" label="Harvesting method" :value="$harvest?->method->value" :required="true" :options="collect(\App\Enums\HarvestMethod::cases())->mapWithKeys(fn($method) => [$method->value => $method->label()])->all()" />
                <x-production-field name="quantity_kg" label="Quantity (kg)" type="number" :value="$harvest?->quantity_kg" :required="false" min="0.01" max="99999999.99" step="0.01" help="Leave blank if the total is not known yet." />
                <x-production-field name="status" label="Harvest status" :value="$harvest?->status->value ?? 'declared'" :required="true" :options="collect(\App\Enums\HarvestStatus::cases())->filter(fn($status) => $status->isProducerSettable())->mapWithKeys(fn($status) => [$status->value => $status->label()])->all()" help="Milled is unlocked when a mill completes your request." />
            </div>
            <x-production-field name="notes" label="Harvest notes" type="textarea" :value="$harvest?->notes" maxlength="3000" />
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><x-primary-button>{{ $harvest ? 'Save harvest' : 'Declare harvest' }}</x-primary-button><a class="btn-secondary" href="{{ $harvest ? route('producer.harvests.show', $harvest->id) : route('producer.harvests.index') }}">Cancel</a></div>
        </form>
    </x-card>
@endsection
