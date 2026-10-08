@extends('layouts.admin')
@section('title', 'Product details')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p class="eyebrow mb-4 text-olive-600">{{ $product->brand }} / Product #{{ $product->id }}</p><h1 class="display-title text-4xl sm:text-5xl">{{ $product->name }}</h1><p class="mt-4 text-sm text-stone-500">Managed by {{ $product->createdBy?->name ?? 'Unknown producer' }}</p></div>
        <div class="flex flex-wrap gap-3"><a class="btn-secondary" href="{{ route($admin ? 'admin.products.index' : 'producer.products.index') }}">Back to products</a><a class="btn-primary" href="{{ route($admin ? 'admin.products.edit' : 'producer.products.edit', $product) }}">Edit product <x-icon name="arrow" /></a></div>
    </div>
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <x-card>
            <div class="flex flex-wrap items-center gap-3"><span class="role-badge">{{ $product->public_status->label() }}</span>@if($product->archived_at)<span class="text-xs text-stone-500">Archived {{ $product->archived_at->format('d/m/Y') }}</span>@endif</div>
            <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2">
                <div><dt class="text-stone-500">Bottle volume</dt><dd class="mt-2 font-semibold">{{ $product->bottle_volume_ml }} ml</dd></div>
                <div><dt class="text-stone-500">Packaging date</dt><dd class="mt-2 font-semibold">{{ $product->packaging_date->format('d/m/Y') }}</dd></div>
                <div><dt class="text-stone-500">Oil lot</dt><dd class="mt-2 font-semibold">{{ $product->oilLot()?->lotCode ?? 'Unavailable' }}</dd></div>
                <div><dt class="text-stone-500">Lot grade</dt><dd class="mt-2 font-semibold">{{ $product->oilLot()?->grade ?? 'Unavailable' }}</dd></div>
            </dl>
            <div class="mt-7 border-t border-stone-100 pt-6">
                <h2 class="font-display text-2xl">Recorded transport emissions</h2>
                <p class="mt-3 text-sm leading-6 text-stone-500">{{ number_format($product->totalCo2Kg(), 2) }} kg estimated across non-cancelled shipments.</p>
            </div>
        </x-card>
        <div class="space-y-6">
            <x-card>
                <h2 class="font-display text-2xl">Public trace link</h2>
                <div class="mx-auto my-5 w-fit rounded-xl border border-stone-100 bg-white p-3">{!! $product->qrSvg() !!}</div>
                <div x-data="{ copied: false }">
                    <label for="trace-url" class="form-label">Printable product URL</label>
                    <input id="trace-url" x-ref="traceUrl" readonly value="{{ $product->traceUrl() }}" class="form-input mt-2 w-full">
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText($refs.traceUrl.value).then(() => copied = true)">Copy link</button>
                        <a class="text-link" href="{{ $product->traceUrl() }}" target="_blank" rel="noopener">Open trace URL</a>
                        <span x-cloak x-show="copied" class="text-xs text-olive-700">Copied</span>
                    </div>
                </div>
            </x-card>
            <x-card>
                <h2 class="font-display text-2xl">Product controls</h2>
                @if($admin)
                    <form method="POST" action="{{ route('admin.products.visibility', $product) }}" class="mt-5 flex flex-wrap items-center gap-3">@csrf @method('PATCH')
                        <input type="hidden" name="public_status" value="{{ $product->public_status === \App\Enums\OilProductPublicStatus::Visible ? 'hidden' : 'visible' }}">
                        <button class="btn-secondary" type="submit" @disabled($product->archived_at && $product->public_status === \App\Enums\OilProductPublicStatus::Hidden)>{{ $product->public_status === \App\Enums\OilProductPublicStatus::Visible ? 'Hide public page' : 'Unhide public page' }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('producer.products.visibility', $product) }}" class="mt-5">@csrf @method('PATCH')<button class="btn-secondary" type="submit" @disabled($product->archived_at)>{{ $product->public_status === \App\Enums\OilProductPublicStatus::Visible ? 'Hide public page' : 'Show public page' }}</button></form>
                @endif
                @unless($product->archived_at)
                    <form method="POST" action="{{ route($admin ? 'admin.products.archive' : 'producer.products.archive', $product) }}" class="mt-3" onsubmit="return confirm('Archive this product? Its traceability history will be preserved.')">@csrf @method('PATCH')<button class="btn-danger" type="submit">Archive product</button></form>
                @endunless
            </x-card>
        </div>
    </div>
    <x-card class="mt-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><p class="eyebrow text-olive-600">Distribution history</p><h2 class="mt-2 font-display text-2xl">Shipments</h2></div><span class="text-sm text-stone-500">{{ $product->shipments->count() }} shipment(s)</span></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[650px] text-left text-sm"><thead class="border-b border-stone-100 text-xs uppercase tracking-wide text-stone-500"><tr><th class="py-3">Route</th><th class="py-3">Distributor</th><th class="py-3">Departure</th><th class="py-3">Status</th><th class="py-3"></th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($product->shipments as $shipment)
                        <tr><td class="py-4">{{ $shipment->departure_location }} → {{ $shipment->destination }}</td><td class="py-4">{{ $shipment->distributorProfile->company_name }}</td><td class="py-4">{{ $shipment->departure_date->format('d/m/Y') }}</td><td class="py-4"><span class="role-badge">{{ $shipment->status->label() }}</span></td><td class="py-4 text-right">@if($admin)<a class="text-link" href="{{ route('admin.shipments.show', $shipment) }}">View</a>@endif</td></tr>
                    @empty<tr><td colspan="5" class="py-8 text-center text-sm text-stone-500">No shipments recorded for this product.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
