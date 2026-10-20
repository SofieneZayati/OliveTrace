@extends('layouts.admin')
@section('title', 'Distribution dashboard')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Distribution overview</p>
            <h1 class="display-title text-4xl sm:text-5xl">The journey at a glance.</h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-stone-500">
                {{ $admin ? 'All distributor shipments across OliveTrace.' : 'Only shipments linked to your distributor profile.' }}
            </p>
        </div>
        <a class="btn-secondary" href="{{ route($admin ? 'admin.shipments.index' : 'distributor.shipments.index') }}">View shipments</a>
    </div>

    <x-card class="mb-7">
        <form method="GET" class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_auto]">
            <div>
                <x-input-label for="date_from" value="Departure from" />
                <x-text-input id="date_from" name="date_from" type="date" class="mt-2 w-full" :value="$filters['date_from'] ?? ''" />
            </div>
            <div>
                <x-input-label for="date_to" value="Departure to" />
                <x-text-input id="date_to" name="date_to" type="date" class="mt-2 w-full" :value="$filters['date_to'] ?? ''" />
            </div>
            <div class="flex items-center gap-3">
                <x-primary-button>Filter</x-primary-button>
                <a class="text-link" href="{{ route($admin ? 'admin.distributor.dashboard' : 'distributor.dashboard') }}">Reset</a>
            </div>
        </form>
        <x-input-error :messages="$errors->get('date_from')" class="mt-2" />
        <x-input-error :messages="$errors->get('date_to')" class="mt-2" />
    </x-card>

    @php($hasShipments = array_sum($metrics['status_counts']) > 0)
    @if(! $hasShipments)
        <x-card class="mb-7">
            <h2 class="font-display text-2xl">No shipments in this period.</h2>
            <p class="mt-3 text-sm leading-7 text-stone-500">Plan a shipment or widen the selected dates to see distribution metrics.</p>
        </x-card>
    @endif

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-card><p class="text-sm text-stone-500">Total distance</p><p class="mt-3 font-display text-3xl">{{ number_format($metrics['total_distance'], 0) }} km</p></x-card>
        <x-card><p class="text-sm text-stone-500">Estimated CO₂</p><p class="mt-3 font-display text-3xl">{{ number_format($metrics['total_co2'], 2) }} kg</p></x-card>
        <x-card><p class="text-sm text-stone-500">Average delivery</p><p class="mt-3 font-display text-3xl">{{ $metrics['average_delivery_days'] === null ? '—' : number_format($metrics['average_delivery_days'], 1).' days' }}</p></x-card>
        <x-card><p class="text-sm text-stone-500">Overdue shipments</p><p class="mt-3 font-display text-3xl">{{ $metrics['overdue_count'] }}</p></x-card>
    </div>

    <div class="mt-7 grid gap-7 lg:grid-cols-2">
        <x-card>
            <h2 class="font-display text-2xl">Shipment status</h2>
            <div class="mt-6 space-y-4" aria-label="Shipment status chart">
                @foreach(['planned' => 'Planned', 'in_transit' => 'In transit', 'delivered' => 'Delivered'] as $status => $label)
                    @php($count = $metrics['status_counts'][$status] ?? 0)
                    <div>
                        <div class="flex justify-between text-sm"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                        <div class="mt-2 h-3 rounded-full bg-olive-100"><div class="h-3 rounded-full bg-olive-600" style="width: {{ $hasShipments ? ($count / array_sum($metrics['status_counts'])) * 100 : 0 }}%" role="img" aria-label="{{ $label }}: {{ $count }}"></div></div>
                    </div>
                @endforeach
            </div>
        </x-card>
        <x-card>
            <h2 class="font-display text-2xl">Transport types</h2>
            <div class="mt-6 overflow-x-auto">
                <svg viewBox="0 0 360 180" class="h-48 w-full min-w-[300px]" role="img" aria-label="Shipments by transport type">
                    @php($transportTotal = array_sum($metrics['transport_counts']))
                    @foreach($metrics['transport_counts'] as $type => $count)
                        @php($index = $loop->index)
                        <rect x="{{ 20 + ($index * 66) }}" y="{{ 150 - ($transportTotal ? ($count / $transportTotal) * 110 : 0) }}" width="42" height="{{ $transportTotal ? ($count / $transportTotal) * 110 : 0 }}" rx="5" fill="#52734d" />
                        <text x="{{ 41 + ($index * 66) }}" y="171" text-anchor="middle" class="fill-stone-500 text-[10px]">{{ ucfirst(str_replace('_', ' ', $type)) }}</text>
                        <text x="{{ 41 + ($index * 66) }}" y="{{ 143 - ($transportTotal ? ($count / $transportTotal) * 110 : 0) }}" text-anchor="middle" class="fill-olive-900 text-xs">{{ $count }}</text>
                    @endforeach
                </svg>
            </div>
        </x-card>
    </div>
@endsection
