@extends('layouts.admin')
@section('title', 'Farm activity journal')
@section('content')
    @php
        $prefix = $admin ? 'admin' : 'producer';
        $writable = !$admin && $farm->status === \App\Enums\FarmStatus::Active && $farm->producerProfile->is_active;
    @endphp
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p class="eyebrow mb-4 text-olive-600">{{ $farm->name }} / Farm care</p><h1 class="display-title text-4xl sm:text-5xl">Every action has a story.</h1><p class="mt-4 text-sm leading-6 text-stone-500">Plan work, record its completion, and keep a private history of this farm.</p></div>
        <div class="flex flex-wrap gap-3"><a class="btn-secondary" href="{{ route($prefix.'.farms.show', $farm->id) }}">Back to farm</a>@if($writable)<a class="btn-primary" href="{{ route('producer.farms.activities.create', $farm->id) }}">Plan an activity <x-icon name="arrow" /></a>@endif</div>
    </div>
    <div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach(['Planned' => $counts['planned'], 'Overdue' => $counts['overdue'], 'Completed' => $counts['completed'], 'Recorded water this month' => number_format((float) $counts['water'], 2).' m³'] as $label => $value)
            <x-card><p class="text-xs text-stone-500">{{ $label }}</p><p class="mt-3 font-display text-3xl">{{ $value }}</p></x-card>
        @endforeach
    </div>
    @unless($writable)<p class="mb-6 rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">This journal is read-only{{ $admin ? ' for administrator inspection' : ' while the farm or producer profile is inactive' }}. Existing history remains available.</p>@endunless
    <x-card class="mb-7"><form method="GET" class="grid items-end gap-4 sm:grid-cols-3">
        <x-production-field name="status" label="Status" :value="request('status')" :preserve-input="false" :options="['planned' => 'Planned', 'overdue' => 'Overdue', 'completed' => 'Completed', 'cancelled' => 'Cancelled']" />
        <x-production-field name="type" label="Activity type" :value="request('type')" :preserve-input="false" :options="collect(\App\Enums\FarmActivityType::cases())->mapWithKeys(fn($type) => [$type->value => $type->label()])->all()" />
        <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route($prefix.'.farms.activities.index', $farm->id) }}">Reset</a></div>
    </form></x-card>
    <div class="space-y-5">
        @forelse($activities as $entry)
            <x-card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div><p class="eyebrow text-olive-600">{{ $entry->type->label() }}</p><h2 class="mt-3 font-display text-2xl">{{ $entry->title }}</h2><p class="mt-3 text-sm text-stone-500">Planned for {{ $entry->planned_on->format('d M Y') }}@if($entry->completed_on) · Completed {{ $entry->completed_on->format('d M Y') }}@endif</p></div>
                    <div class="flex items-center gap-3"><span class="role-badge">{{ $entry->status->label() }}</span>@if($entry->isOverdue())<span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">Overdue</span>@endif</div>
                </div>
                @if($entry->notes)<p class="mt-5 whitespace-pre-line text-sm leading-7 text-stone-600">{{ $entry->notes }}</p>@endif
                @if($entry->water_m3 !== null)<p class="mt-5 text-sm font-semibold">Recorded irrigation water: {{ $entry->water_m3 }} m³ <span class="font-normal text-stone-500">(producer declaration)</span></p>@endif
                @if($entry->advice_report_id ?? null)<a class="text-link mt-4 inline-block" href="{{ route($prefix.'.farms.reports.show', [$farm->id, $entry->advice_report_id]) }}">View the AI report behind this activity</a>@endif
                @if($writable && $entry->status === \App\Enums\FarmActivityStatus::Planned)
                    <div class="mt-6 border-t border-stone-100 pt-5">
                        <form method="POST" action="{{ route('producer.farms.activities.status', [$farm->id, $entry->id]) }}" class="flex flex-wrap items-end gap-4" novalidate>
                            @csrf @method('PATCH')<input type="hidden" name="status" value="completed" />
                            <div><label class="form-label" for="completed-{{ $entry->id }}">Completion date</label><input class="form-input mt-2" type="date" id="completed-{{ $entry->id }}" name="completed_on" min="{{ $entry->planned_on->toDateString() }}" max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" required /></div>
                            @if($entry->type === \App\Enums\FarmActivityType::Irrigation)<div><label class="form-label" for="water-{{ $entry->id }}">Actual water used (m³)</label><input class="form-input mt-2 w-44" type="number" id="water-{{ $entry->id }}" name="water_m3" min="0.01" max="9999999999.99" step="0.01" required /></div>@endif
                            <button class="btn-primary" type="submit">Mark completed</button><a class="btn-secondary" href="{{ route('producer.farms.activities.edit', [$farm->id, $entry->id]) }}">Edit plan</a>
                        </form>
                        <form method="POST" action="{{ route('producer.farms.activities.status', [$farm->id, $entry->id]) }}" class="mt-4">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled" /><button class="text-link" type="submit">Cancel this activity</button></form>
                    </div>
                @endif
            </x-card>
        @empty
            <x-card><h2 class="font-display text-2xl">A fresh page.</h2><p class="mt-3 text-sm leading-6 text-stone-500">No activities match this view. Start with an inspection or plan the next piece of farm care.</p></x-card>
        @endforelse
    </div>
    <div class="mt-7">{{ $activities->links() }}</div>
    <p class="mt-6 text-xs leading-6 text-stone-500">Completed and cancelled entries are retained as history. Water totals sum completed irrigation declarations; they are not meter readings or proof of water savings.</p>
@endsection
