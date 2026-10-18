@extends('layouts.admin')
@section('title', 'Farm AI reports')
@section('content')
    @php($prefix = $admin ? 'admin' : 'producer')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">{{ $farm->name }} / Sustainability assistant</p><h1 class="display-title text-4xl sm:text-5xl">Advice you can follow up.</h1><p class="mt-4 max-w-2xl text-sm leading-7 text-stone-500">Each report keeps a dated snapshot of farm facts and selected activity records. Review a recommendation before turning it into planned work.</p></div><a class="btn-secondary" href="{{ route($prefix.'.farms.show', $farm->id) }}">Back to farm</a></div>
    <x-card class="mb-7">
        <div class="flex flex-wrap items-center justify-between gap-6"><div class="max-w-2xl"><h2 class="font-display text-2xl">A fresh perspective on your records.</h2><p class="mt-3 text-sm leading-7 text-stone-500">AI receives region, area, variety, cultivation practices and up to 30 structured activity records from the last 90 days through the next 30 days. Names, contact details, exact GPS, activity titles and free-text notes are excluded.</p></div>
            @if($farm->status === \App\Enums\FarmStatus::Active && $farm->producerProfile->is_active)
                <form method="POST" action="{{ route($prefix.'.farms.reports.store', $farm->id) }}" x-data="{ loading: false }" @submit="loading = true">@csrf<button class="btn-primary" type="submit" :disabled="loading"><span x-show="!loading">Generate saved action plan</span><span x-show="loading" x-cloak role="status">Preparing your report…</span></button></form>
            @else<p class="text-sm font-medium">Generation is paused while this farm or producer profile is inactive.</p>@endif
        </div>
    </x-card>
    <div class="space-y-5">
        @forelse($reports as $saved)
            <x-card><div class="flex flex-wrap items-start justify-between gap-5"><div><p class="eyebrow text-olive-600">Report #{{ $saved->id }} · {{ $saved->created_at->format('d M Y, H:i') }}</p><h2 class="mt-3 font-display text-2xl">{{ \Illuminate\Support\Str::limit($saved->advice['summary'], 180) }}</h2><p class="mt-4 text-sm text-stone-500">{{ count($saved->advice['recommendations']) }} recommendations · {{ $saved->activities_count }} added to the journal · {{ $saved->provider }} / {{ $saved->model }}</p></div><a class="btn-secondary" href="{{ route($prefix.'.farms.reports.show', [$farm->id, $saved->id]) }}">Read report <x-icon name="arrow" /></a></div></x-card>
        @empty<x-card><h2 class="font-display text-2xl">Your first report starts here.</h2><p class="mt-3 text-sm leading-7 text-stone-500">Record some farm activities, then generate an action plan. A new farm can also receive suggestions about information to collect.</p></x-card>@endforelse
    </div>
    <div class="mt-7">{{ $reports->links() }}</div>
    <p class="mt-6 text-xs leading-6 text-stone-500">Reports are private, advisory snapshots. Generating or viewing a report never changes farm practices or creates activities automatically.</p>
@endsection
