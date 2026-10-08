@extends('layouts.admin')
@section('title', 'Shipment details')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">Shipment #{{ $shipment->id }} / {{ $shipment->transport_type->label() }}</p><h1 class="display-title text-4xl sm:text-5xl">{{ $shipment->departure_location }} <span class="text-olive-500">→</span> {{ $shipment->destination }}</h1><p class="mt-4 text-sm text-stone-500">{{ $shipment->status->label() }}</p></div><div class="flex flex-wrap gap-3"><a class="btn-secondary" href="{{ route(request()->routeIs('admin.*') ? 'admin.shipments.index' : 'distributor.shipments.index') }}">Back to shipments</a>@unless(request()->routeIs('admin.*'))<a class="btn-primary" href="{{ route('distributor.shipments.edit', $shipment) }}">Edit shipment</a>@endunless</div></div>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card><h2 class="font-display text-2xl">Shipment record</h2><dl class="mt-6 grid gap-5 text-sm sm:grid-cols-2"><div><dt class="text-stone-500">Product</dt><dd class="mt-2 font-semibold">{{ $shipment->oilProduct->name }}</dd></div><div><dt class="text-stone-500">Brand</dt><dd class="mt-2 font-semibold">{{ $shipment->oilProduct->brand }}</dd></div><div><dt class="text-stone-500">Status</dt><dd class="mt-2"><span class="role-badge">{{ $shipment->status->label() }}</span></dd></div><div><dt class="text-stone-500">Transport</dt><dd class="mt-2 font-semibold">{{ $shipment->transport_type->label() }}</dd></div><div><dt class="text-stone-500">Distance</dt><dd class="mt-2 font-semibold">{{ $shipment->distance_km }} km</dd></div><div><dt class="text-stone-500">Bottle quantity</dt><dd class="mt-2 font-semibold">{{ number_format($shipment->quantity_bottles) }}</dd></div><div><dt class="text-stone-500">Estimated CO₂</dt><dd class="mt-2 font-semibold">{{ $shipment->co2_estimate === null ? 'Not available' : $shipment->co2_estimate.' kg' }}</dd></div></dl></x-card>
        <x-card><h2 class="font-display text-2xl">Journey and distributor</h2><dl class="mt-6 grid gap-5 text-sm"><div><dt class="text-stone-500">Distributor</dt><dd class="mt-2 font-semibold">{{ $shipment->distributorProfile->company_name }}</dd></div><div><dt class="text-stone-500">Departure date</dt><dd class="mt-2 font-semibold">{{ $shipment->departure_date->format('d/m/Y') }}</dd></div><div><dt class="text-stone-500">Arrival date</dt><dd class="mt-2 font-semibold">{{ $shipment->arrival_date?->format('d/m/Y') ?? 'Not recorded' }}</dd></div></dl></x-card>
    </div>
    <section class="mt-6">
        <x-card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><h2 class="font-display text-2xl">Distribution impact assistant</h2><p class="mt-2 max-w-2xl text-sm text-stone-600">Get an advisory estimate and a lower-impact transport suggestion. Historical shipment data is never changed.</p></div>
                <form method="POST" action="{{ route(request()->routeIs('admin.*') ? 'admin.shipments.impact' : 'distributor.shipments.impact', $shipment) }}">@csrf<button class="btn-primary" type="submit">Analyze impact</button></form>
            </div>
            @if (session('impactAdvice'))
                @php($impactAdvice = session('impactAdvice'))
                <div class="mt-6 rounded-xl border border-olive-200 bg-olive-50 p-5" role="status">
                    <p class="text-xs font-semibold uppercase tracking-wide text-olive-800">{{ $impactAdvice['source'] === 'ai' ? 'AI suggestion, advisory only' : 'Rule-based suggestion' }}</p>
                    <p class="mt-3 text-sm leading-6 text-stone-800">{{ $impactAdvice['summary'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-stone-800">{{ $impactAdvice['alternative'] }}</p>
                    <p class="mt-3 text-xs text-stone-600">This advice is informational only; it does not update this historical shipment.</p>
                </div>
            @endif
        </x-card>
    </section>
@endsection
