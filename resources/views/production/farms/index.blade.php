@extends('layouts.admin')
@section('title', $admin ? 'All farms' : 'My farms')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">Where the story takes root</p><h1 class="display-title text-4xl">{{ $admin ? 'Farms across the community.' : 'Your land. Your origin.' }}</h1><p class="mt-4 text-sm text-stone-500">{{ $farms->total() }} farms and parcels {{ $admin ? 'across all producers' : 'in your workspace' }}.</p></div>@unless($admin)<a class="btn-primary" href="{{ route('producer.farms.create') }}">Add a farm <x-icon name="arrow" /></a>@endunless</div>
    <x-card class="mb-7"><form method="GET" class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_auto]">
        <x-production-field name="search" label="Search farm or producer" :value="request('search')" maxlength="100" />
        <x-production-field name="governorate" label="Governorate" :value="request('governorate')" :options="array_combine(\App\Support\ProductionOptions::GOVERNORATES, \App\Support\ProductionOptions::GOVERNORATES)" />
        <x-production-field name="status" label="Status" :value="request('status')" :options="collect(\App\Enums\FarmStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->all()" />
        <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route($admin ? 'admin.farms.index' : 'producer.farms.index') }}">Reset</a></div>
    </form></x-card>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse($farms as $farm)
            <x-card class="flex flex-col">
                <div class="mb-6 flex items-center justify-between"><span class="grid h-12 w-12 place-items-center rounded-full bg-olive-50 text-olive-600"><x-icon name="leaf" class="h-6 w-6" /></span><span class="role-badge">{{ $farm->status->label() }}</span></div>
                <p class="eyebrow text-stone-500">{{ $farm->governorate }} @if($farm->delegation) / {{ $farm->delegation }} @endif</p>
                <h2 class="mt-3 font-display text-3xl">{{ $farm->name }}</h2>
                <p class="mt-3 text-sm text-stone-500">{{ $farm->producerProfile->displayName }}</p>
                <div class="my-6 grid grid-cols-2 gap-4 border-y border-stone-100 py-5 text-sm"><div><p class="text-xs text-stone-500">Area</p><p class="mt-2 font-semibold">{{ $farm->areaHa }} ha</p></div><div><p class="text-xs text-stone-500">Olive variety</p><p class="mt-2 font-semibold">{{ $farm->oliveVariety }}</p></div></div>
                <a class="text-link mt-auto flex items-center justify-between" href="{{ route($admin ? 'admin.farms.show' : 'producer.farms.show', $farm->id) }}">View farm <span class="sr-only">{{ $farm->name }}</span><x-icon name="arrow" /></a>
            </x-card>
        @empty<x-card class="md:col-span-2 xl:col-span-3"><h2 class="font-display text-2xl">No farms to show yet.</h2><p class="mt-3 text-sm text-stone-500">Try different filters{{ $admin ? '.' : ' or add your first parcel.' }}</p></x-card>@endforelse
    </div>
    <div class="mt-8">{{ $farms->links() }}</div>
@endsection
