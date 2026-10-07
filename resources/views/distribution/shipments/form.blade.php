@extends('layouts.admin')
@section('title', $shipment ? 'Edit shipment' : 'Plan shipment')
@section('content')
    <div class="mb-8 max-w-2xl"><p class="eyebrow mb-4 text-olive-600">Keep the product journey in view</p><h1 class="display-title text-4xl">{{ $shipment ? 'Update shipment details.' : 'Plan a shipment.' }}</h1><p class="mt-4 text-sm leading-6 text-stone-500">Shipment records preserve the product and distributor history. Emissions are not entered here.</p></div>
    <x-card class="max-w-3xl">
        <x-validation-errors class="mb-6" />
        <form method="POST" action="{{ $shipment ? route('distributor.shipments.update', $shipment) : route('distributor.shipments.store') }}" class="space-y-6">
            @csrf
            @if($shipment) @method('PATCH')<input type="hidden" name="status" value="{{ old('status', $shipment->status->value) }}">@endif
            <div class="grid gap-6 sm:grid-cols-2">
                <x-production-field name="oil_product_id" label="Product" :value="$shipment?->oil_product_id" :options="$products->mapWithKeys(fn ($product) => [$product->id => $product->name.' - '.$product->brand])->all()" :required="true" />
                <x-production-field name="transport_type" label="Transport type" :value="$shipment?->transport_type?->value ?? 'truck'" :options="collect(\App\Enums\TransportType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" :required="true" />
                <x-production-field name="departure_location" label="Departure location" :value="$shipment?->departure_location ?? 'Sfax, Tunisia'" maxlength="255" :required="true" />
                <x-production-field name="destination" label="Destination" :value="$shipment?->destination ?? 'Tunis, Tunisia'" maxlength="255" :required="true" />
                <x-production-field name="departure_date" label="Departure date" type="date" :value="$shipment?->departure_date?->format('Y-m-d') ?? now()->format('Y-m-d')" :required="true" />
                <x-production-field name="arrival_date" label="Arrival date (optional)" type="date" :value="$shipment?->arrival_date?->format('Y-m-d')" />
                <x-production-field name="distance_km" label="Distance (km)" type="number" :value="$shipment?->distance_km ?? '270.00'" min="0.01" max="20000" step="0.01" :required="true" />
            </div>
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><x-primary-button>{{ $shipment ? 'Save shipment' : 'Plan shipment' }}</x-primary-button><a class="btn-secondary" href="{{ $shipment ? route('distributor.shipments.show', $shipment) : route('distributor.shipments.index') }}">Cancel</a></div>
        </form>
        @if($shipment && $allowedStatuses->isNotEmpty())
            <div class="mt-8 border-t border-stone-100 pt-6">
                <h2 class="font-display text-2xl">Update shipment status</h2>
                <form method="POST" action="{{ route('distributor.shipments.status', $shipment) }}" class="mt-4 flex flex-wrap items-end gap-4">@csrf @method('PATCH')
                    <x-production-field name="status" label="New status" :value="$shipment->status->value" :options="$allowedStatuses->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" :required="true" />
                    <button class="btn-secondary" type="submit">Update status</button>
                </form>
                @error('status')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        @endif
    </x-card>
@endsection
