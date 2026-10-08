@extends('layouts.admin')
@section('title', 'My oil lots')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">From the mill to your bottle</p>
            <h1 class="display-title text-4xl">Your oil lots</h1>
            <p class="mt-4 text-sm text-stone-500">Track the oil produced from your harvests.</p>
        </div>
    </div>
    <x-card>
        <form method="GET" class="mb-6 grid items-end gap-4 sm:grid-cols-3">
            <x-production-field name="search" label="Search lot, farm or harvest" :value="request('search')" maxlength="100" />
            <x-production-field name="quality" label="Quality grade" :value="request('quality')" :options="collect(\App\Enums\OilQuality::cases())->mapWithKeys(fn($q) => [$q->value => $q->label()])->all()" />
            <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route('producer.oil-lots.index') }}">Reset</a></div>
        </form>
        @forelse($oilLots as $oilLot)
            <div class="mt-6 flex flex-wrap items-start justify-between gap-4 border-t border-stone-100 pt-6 first:mt-0 first:border-0 first:pt-0">
                <div class="grid gap-4 sm:grid-cols-4">
                    <div><p class="text-xs text-stone-500">Lot number</p><p class="mt-2 text-sm font-semibold"><a class="text-link" href="{{ route('producer.oil-lots.show', $oilLot->id) }}">{{ $oilLot->lot_number }}</a></p></div>
                    <div><p class="text-xs text-stone-500">Quality</p><p class="mt-2 font-semibold">@if($oilLot->quality_grade)<span class="role-badge">{{ $oilLot->quality_grade->label() }}</span>@else<span class="text-sm text-stone-500">Not graded</span>@endif</p></div>
                    <div><p class="text-xs text-stone-500">Volume</p><p class="mt-2 text-sm font-semibold">{{ $oilLot->liters !== null ? $oilLot->liters.' L' : 'Not recorded' }}</p></div>
                    <div><p class="text-xs text-stone-500">Origin</p><p class="mt-2 text-sm font-semibold">{{ $oilLot->millRequest?->harvest?->farm?->name ?? 'Declared by you' }}</p></div>
                </div>
                <div class="flex items-center gap-3">
                    <a class="btn-secondary" href="{{ route('producer.oil-lots.show', $oilLot->id) }}">View details</a>
                </div>
            </div>
        @empty
            <p class="mt-3 text-sm leading-6 text-stone-500">No oil lots yet. Complete a mill request to generate oil lots.</p>
        @endforelse
        <div class="mt-8">{{ $oilLots->links() }}</div>
    </x-card>
@endsection
