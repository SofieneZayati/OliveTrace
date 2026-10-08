@extends('layouts.admin')
@section('title', $admin ? 'All shipments' : 'My shipments')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p class="eyebrow mb-4 text-olive-600">A clear route from origin to destination</p><h1 class="display-title text-4xl">{{ $admin ? 'Shipments across OliveTrace.' : 'Your shipments.' }}</h1><p class="mt-4 text-sm text-stone-500">{{ $shipments->total() }} shipment(s) {{ $admin ? 'recorded' : 'in your workspace' }}.</p></div>
        @unless($admin)<a class="btn-primary" href="{{ route('distributor.shipments.create') }}">Plan a shipment <x-icon name="arrow" /></a>@endunless
    </div>
    <x-card class="mb-7"><form method="GET" class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-production-field name="status" label="Shipment status" :value="$filters['status'] ?? ''" :options="collect(\App\Enums\ShipmentStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
        <x-production-field name="date_from" label="Departure from" type="date" :value="$filters['date_from'] ?? ''" />
        <x-production-field name="date_to" label="Departure to" type="date" :value="$filters['date_to'] ?? ''" />
        @if($admin)<x-production-field name="owner_id" label="Distributor user ID" type="number" :value="$filters['owner_id'] ?? ''" min="1" />@endif
        <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route($admin ? 'admin.shipments.index' : 'distributor.shipments.index') }}">Reset</a></div>
    </form></x-card>
    <x-card class="overflow-x-auto p-0">
        <table class="w-full min-w-[900px] text-left text-sm"><thead class="border-b border-stone-100 bg-stone-50/70 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-4">Route</th><th class="px-5 py-4">Product</th><th class="px-5 py-4">Distributor</th><th class="px-5 py-4">Departure</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">CO₂ estimate</th><th class="px-5 py-4"></th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse($shipments as $shipment)
                    <tr><td class="px-5 py-4"><span class="font-semibold">{{ $shipment->departure_location }}</span><span class="text-stone-400"> → </span>{{ $shipment->destination }}</td><td class="px-5 py-4">{{ $shipment->oilProduct->name }}</td><td class="px-5 py-4">{{ $shipment->distributorProfile->company_name }}</td><td class="px-5 py-4">{{ $shipment->departure_date->format('d/m/Y') }}</td><td class="px-5 py-4"><span class="role-badge">{{ $shipment->status->label() }}</span></td><td class="px-5 py-4">{{ $shipment->co2_estimate === null ? 'Not available' : $shipment->co2_estimate.' kg' }}</td><td class="px-5 py-4 text-right"><a class="text-link" href="{{ route($admin ? 'admin.shipments.show' : 'distributor.shipments.show', $shipment) }}">View</a></td></tr>
                @empty<tr><td colspan="7" class="px-5 py-12 text-center"><h2 class="font-display text-2xl">No shipments to show yet.</h2><p class="mt-3 text-sm text-stone-500">Try different filters{{ $admin ? '.' : ' or plan your first shipment.' }}</p></td></tr>@endforelse
            </tbody>
        </table>
    </x-card>
    <div class="mt-8">{{ $shipments->links() }}</div>
@endsection
