@extends('layouts.admin')
@section('title', $admin ? 'Community farm map' : 'My farm map')
@section('content')
    @php($prefix = $admin ? 'admin' : 'producer')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">Private farm locations</p><h1 class="display-title text-4xl sm:text-5xl">Your land, in perspective.</h1><p class="mt-4 max-w-2xl text-sm leading-7 text-stone-500">{{ $located }} of {{ $total }} matching farms have saved coordinates. {{ $admin ? 'Administrator view includes farms across the community.' : 'This view contains only farms belonging to your producer profile.' }}</p></div><a class="btn-secondary" href="{{ route($prefix.'.farms.index', request()->only('search', 'governorate', 'status')) }}">List view</a></div>
    <x-card class="mb-7"><form method="GET" class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_auto]">
        <x-production-field name="search" label="Search farm or producer" :value="request('search')" maxlength="100" />
        <x-production-field name="governorate" label="Governorate" :value="request('governorate')" :options="array_combine(\App\Support\ProductionOptions::GOVERNORATES, \App\Support\ProductionOptions::GOVERNORATES)" />
        <x-production-field name="status" label="Status" :value="request('status')" :options="collect(\App\Enums\FarmStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->all()" />
        <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route($prefix.'.farms.map') }}">Reset</a></div>
    </form></x-card>
    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-display text-2xl">The places behind your work.</h2><span class="role-badge">{{ count($points) }} mapped</span></div>
        @if($located > $limit)<p class="mt-4 text-sm text-amber-800">Showing the first {{ $limit }} farms by name. Narrow the filters to find a specific location.</p>@endif
        @if($located === 0)<p class="mt-4 text-sm leading-7 text-stone-500">No matching farms have coordinates yet. Open a farm, choose Edit farm, and select its location. No approximate or invented farm locations are shown.</p>@endif
        <x-farm-map :points="$points" />
    </x-card>
    <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($points as $point)<x-card><span class="role-badge">{{ $point['status'] }}</span><h2 class="mt-4 font-display text-2xl">{{ $point['name'] }}</h2><p class="mt-3 text-sm text-stone-500">{{ $point['region'] }}</p><a class="text-link mt-4 inline-block" href="{{ $point['url'] }}">Open farm →</a></x-card>@endforeach
    </div>
    <p class="mt-6 text-xs leading-6 text-stone-500">Locations are producer declarations. Markers represent points, not verified farm boundaries, parcel areas or certification.</p>
@endsection
