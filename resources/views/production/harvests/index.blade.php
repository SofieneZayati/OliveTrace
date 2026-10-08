@extends('layouts.admin')
@section('title', $admin ? 'All harvests' : 'My harvests')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">From the grove to the mill</p><h1 class="display-title text-4xl">{{ $admin ? 'Harvests across the community.' : 'Your harvest ledger.' }}</h1><p class="mt-4 text-sm text-stone-500">{{ $harvests->total() }} harvests {{ $admin ? 'declared by every producer' : 'declared on your farms' }}.</p></div>@unless($admin)<a class="btn-primary" href="{{ route('producer.harvests.create') }}">Declare a harvest <x-icon name="arrow" /></a>@endunless</div>
    <x-card class="mb-7"><form method="GET" class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_auto]">
        <x-production-field name="search" label="Search farm, producer or notes" :value="request('search')" maxlength="100" />
        <x-production-field name="status" label="Harvest status" :value="request('status')" :options="collect(\App\Enums\HarvestStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->all()" />
        <x-production-field name="method" label="Harvest method" :value="request('method')" :options="collect(\App\Enums\HarvestMethod::cases())->mapWithKeys(fn($method) => [$method->value => $method->label()])->all()" />
        <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route($admin ? 'admin.harvests.index' : 'producer.harvests.index') }}">Reset</a></div>
    </form></x-card>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse($harvests as $harvest)
            <x-card class="flex flex-col">
                <div class="mb-6 flex items-center justify-between"><span class="grid h-12 w-12 place-items-center rounded-full bg-olive-50 text-olive-600"><x-icon name="harvest" class="h-6 w-6" /></span><span class="role-badge">{{ $harvest->status->label() }}</span></div>
                <p class="eyebrow text-stone-500">{{ $harvest->farm->governorate }} / {{ $admin ? $harvest->farm->producerProfile->display_name : $harvest->farm->name }}</p>
                <h2 class="mt-3 font-display text-3xl">{{ $harvest->quantity_kg !== null ? $harvest->quantity_kg.' kg' : 'Unknown quantity' }}</h2>
                <p class="mt-3 text-sm text-stone-500">Harvested on {{ $harvest->harvest_date->isoFormat('ll') }} · {{ $harvest->method->label() }}</p>
                <div class="my-6 grid grid-cols-2 gap-4 border-y border-stone-100 py-5 text-sm"><div><p class="text-xs text-stone-500">Expected end</p><p class="mt-2 font-semibold">{{ $harvest->expected_end_date?->isoFormat('ll') ?? 'Not set' }}</p></div><div><p class="text-xs text-stone-500">Milling</p><p class="mt-2 font-semibold">{{ $harvest->millingStatus()?->label() ?? 'Not requested' }}</p></div></div>
                <a class="text-link mt-auto flex items-center justify-between" href="{{ route($admin ? 'admin.harvests.show' : 'producer.harvests.show', $harvest->id) }}">View harvest <span class="sr-only">{{ $harvest->farm->name }}</span><x-icon name="arrow" /></a>
            </x-card>
        @empty<x-card class="md:col-span-2 xl:col-span-3"><h2 class="font-display text-2xl">No harvests to show yet.</h2><p class="mt-3 text-sm text-stone-500">Try different filters{{ $admin ? '.' : ' or declare your first harvest from a farm page.' }}</p></x-card>@endforelse
    </div>
    <div class="mt-8">{{ $harvests->links() }}</div>
@endsection
