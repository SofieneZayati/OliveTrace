@extends('layouts.admin')
@section('title', $activity ? 'Edit planned activity' : 'Plan farm activity')
@section('content')
    <div class="mb-8"><p class="eyebrow mb-4 text-olive-600">{{ $farm->name }} / Activity journal</p><h1 class="display-title text-4xl">{{ $activity ? 'Adjust the plan.' : 'Plan the next step.' }}</h1><p class="mt-4 max-w-2xl text-sm leading-6 text-stone-500">Record farm care here. Complete the activity afterwards to record what happened. Harvests stay in the harvest workspace.</p></div>
    <x-card class="max-w-3xl">
        <form method="POST" action="{{ $activity ? route('producer.farms.activities.update', [$farm->id, $activity->id]) : route('producer.farms.activities.store', $farm->id) }}" class="space-y-6" novalidate>
            @csrf
            @if($activity) @method('PATCH') @endif
            <x-production-field name="title" label="Activity title" :value="$activity?->title" :required="true" maxlength="150" help="For example: inspect the drip lines on the north parcel." />
            <div class="grid gap-6 sm:grid-cols-2">
                <x-production-field name="type" label="Activity type" :value="$activity?->type->value" :required="true" :options="collect(\App\Enums\FarmActivityType::cases())->mapWithKeys(fn($type) => [$type->value => $type->label()])->all()" />
                <x-production-field name="planned_on" label="Planned date" type="date" :value="$activity?->planned_on->toDateString() ?? today()->toDateString()" :required="true" min="2000-01-01" :max="today()->addYear()->toDateString()" help="Past dates are allowed for recording work retrospectively; future plans are limited to one year." />
            </div>
            <x-production-field name="notes" label="Private notes" type="textarea" :value="$activity?->notes" maxlength="2000" help="Visible to the farm owner and administrators. These notes and the activity title are not sent to AI." />
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><button class="btn-primary" type="submit">{{ $activity ? 'Save changes' : 'Plan activity' }}</button><a class="btn-secondary" href="{{ route('producer.farms.activities.index', $farm->id) }}">Cancel</a></div>
        </form>
    </x-card>
@endsection
