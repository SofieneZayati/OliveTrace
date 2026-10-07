@extends('layouts.front')
@section('title', 'Product traceability')
@section('content')
    @php
        $product = $timeline['product'];
        $origin = $timeline['origin'];
        $harvest = $timeline['harvest'];
        $mill = $timeline['mill'];
        $oilLot = $timeline['oil_lot'];
        $verification = $timeline['verification'];
        $shipments = $timeline['shipments'];
        $feedback = $timeline['feedback'];
        $myFeedback = auth()->check() ? $feedback['items']->firstWhere('consumer_user_id', auth()->id()) : null;
    @endphp
    <div class="mx-auto max-w-3xl">
        <p class="eyebrow mb-4 text-olive-600">From farm to consumer</p>
        <h1 class="display-title mb-4 text-4xl sm:text-5xl">Follow this bottle.</h1>
        <p class="mb-8 text-sm leading-7 text-stone-500">Every step below is read from the module that recorded it. Nothing is copied or rewritten here.</p>

        <x-card class="mb-6">
            <div class="flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-full bg-olive-50"><x-icon name="shield" class="h-6 w-6 text-olive-600" /></span><p class="eyebrow text-olive-600">Product identity</p></div>
            <h2 class="font-display mt-4 text-3xl">{{ $product->name ?? 'Olive oil product #'.$product->id }}</h2>
            @if(!empty($product->brand))<p class="mt-2 text-stone-500">{{ $product->brand }}</p>@endif
            <dl class="mt-6 grid gap-5 text-sm sm:grid-cols-3">
                <div><dt class="text-stone-500">Bottle</dt><dd class="mt-1 font-semibold">{{ $product->bottle_volume_ml ?? '—' }} @if(!empty($product->bottle_volume_ml)) ml @endif</dd></div>
                <div><dt class="text-stone-500">Packaged</dt><dd class="mt-1 font-semibold">{{ $product->packaging_date ?? '—' }}</dd></div>
                <div><dt class="text-stone-500">Oil lot</dt><dd class="mt-1 font-semibold">{{ $oilLot->lot_code ?? '—' }}</dd></div>
            </dl>
        </x-card>

        @if($origin)
            <div class="mb-6"><x-origin-card :origin="$origin" /></div>
        @else
            <x-card class="mb-6"><h2 class="font-display text-2xl">Origin not published yet.</h2><p class="mt-3 text-sm text-stone-500">The producer has not published this farm's origin information.</p></x-card>
        @endif

        <x-card class="mb-6">
            <p class="eyebrow mb-4 text-olive-600">Harvest, mill and oil lot</p>
            @if($harvest)
                <dl class="grid gap-5 text-sm sm:grid-cols-3">
                    <div><dt class="text-stone-500">Harvest date</dt><dd class="mt-1 font-semibold">{{ $harvest->harvest_date ?? '—' }}</dd></div>
                    <div><dt class="text-stone-500">Method</dt><dd class="mt-1 font-semibold">{{ $harvest->method ?? '—' }}</dd></div>
                    <div><dt class="text-stone-500">Quantity</dt><dd class="mt-1 font-semibold">{{ isset($harvest->quantity_kg) ? $harvest->quantity_kg.' kg' : '—' }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-stone-500">No harvest information available for this product yet.</p>
            @endif
            <div class="mt-5 border-t border-stone-100 pt-5">
                @if($mill && isset($mill->mill))
                    <p class="text-sm"><span class="font-semibold">{{ $mill->mill->name ?? 'Registered mill' }}</span> <span class="text-stone-500">· {{ $mill->mill->region ?? '' }} {{ isset($mill->request->status) ? '· request '.$mill->request->status : '' }}</span></p>
                @elseif($mill && !empty($mill->external))
                    <p class="text-sm"><span class="font-semibold">{{ $mill->external }}</span> <span class="text-stone-500">(external mill)</span></p>
                @else
                    <p class="text-sm text-stone-500">No mill information available for this product yet.</p>
                @endif
                @if($oilLot)
                    <dl class="mt-4 grid gap-5 text-sm sm:grid-cols-4">
                        <div><dt class="text-stone-500">Lot code</dt><dd class="mt-1 font-semibold">{{ $oilLot->lot_code ?? '—' }}</dd></div>
                        <div><dt class="text-stone-500">Extraction</dt><dd class="mt-1 font-semibold">{{ $oilLot->extraction_date ?? '—' }}</dd></div>
                        <div><dt class="text-stone-500">Volume</dt><dd class="mt-1 font-semibold">{{ isset($oilLot->volume_l) ? $oilLot->volume_l.' L' : '—' }}</dd></div>
                        <div><dt class="text-stone-500">Grade · Acidity</dt><dd class="mt-1 font-semibold">{{ $oilLot->grade ?? '—' }} · {{ $oilLot->acidity ?? '—' }}</dd></div>
                    </dl>
                @endif
            </div>
        </x-card>

        <x-card class="mb-6">
            <p class="eyebrow mb-4 text-olive-600">Laboratory verification</p>
            @if($verification && $verification['certificate'])
                @php $cert = $verification['certificate']; @endphp
                <p class="flex flex-wrap items-center gap-3"><span class="role-badge">{{ $verification['expired'] ? 'Expired' : ($cert->status ?? $verification['request']->status ?? 'Issued') }}</span><span class="font-display text-2xl">{{ $cert->certificate_number ?? 'Certificate' }}</span></p>
                <dl class="mt-4 grid gap-5 text-sm sm:grid-cols-3">
                    <div><dt class="text-stone-500">Type</dt><dd class="mt-1 font-semibold">{{ $cert->type ?? '—' }}</dd></div>
                    <div><dt class="text-stone-500">Issued</dt><dd class="mt-1 font-semibold">{{ $cert->issue_date ?? '—' }}</dd></div>
                    <div><dt class="text-stone-500">Valid until</dt><dd class="mt-1 font-semibold">{{ $cert->expiry_date ?? '—' }}</dd></div>
                </dl>
            @elseif($verification)
                <p class="text-sm text-stone-500">Verification request status: <span class="font-semibold">{{ $verification['request']->status ?? 'pending' }}</span>. No certificate issued yet.</p>
            @else
                <p class="text-sm text-stone-500">No certificate available for this product yet.</p>
            @endif
        </x-card>

        <x-card class="mb-6">
            <p class="eyebrow mb-4 text-olive-600">Distribution journey</p>
            @forelse($shipments as $shipment)
                <div class="border-t border-stone-100 py-4 first:border-0 first:pt-0">
                    <p class="text-sm font-semibold">{{ $shipment->departure_location ?? '—' }} → {{ $shipment->destination ?? '—' }}</p>
                    <p class="mt-1 text-xs text-stone-500">{{ $shipment->departure_date ?? '' }} @if(!empty($shipment->arrival_date)) · arrived {{ $shipment->arrival_date }} @endif · {{ $shipment->transport_type ?? '' }} @if(isset($shipment->distance_km)) · {{ $shipment->distance_km }} km @endif · <span class="font-semibold">{{ $shipment->status ?? '' }}</span></p>
                </div>
            @empty
                <p class="text-sm text-stone-500">No shipment recorded for this product yet.</p>
            @endforelse
        </x-card>

        <x-card class="mb-6">
            <div class="mb-4 flex items-center justify-between"><p class="eyebrow text-olive-600">Consumer reviews</p>@if($feedback['average'])<p class="flex items-center gap-1 text-sm font-semibold"><x-icon name="star" class="h-4 w-4 text-olive-600" />{{ $feedback['average'] }} / 5 · {{ $feedback['count'] }} review(s)</p>@endif</div>
            @forelse($feedback['items'] as $item)
                <div class="border-t border-stone-100 py-4 first:border-0 first:pt-0">
                    <p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="star" class="h-4 w-4 text-olive-600" />{{ $item->rating }} / 5 <span class="font-normal text-stone-500">· {{ $item->consumer?->name ?? 'Consumer' }} · {{ $item->created_at?->toDateString() }}</span></p>
                    @if($item->comment)<p class="mt-2 text-sm leading-6">{{ $item->comment }}</p>@endif
                </div>
            @empty
                <p class="text-sm text-stone-500">No reviews yet. Be the first to share your tasting.</p>
            @endforelse
            <div class="mt-6 border-t border-stone-100 pt-6">
                @auth
                    @if(auth()->user()->role === \App\Enums\Role::Consumer)
                        <h3 class="font-display mb-4 text-2xl">{{ $myFeedback ? 'Update your review.' : 'Leave your review.' }}</h3>
                        @include('consumer.feedback._form', ['feedback' => $myFeedback, 'productId' => $product->id])
                        @if($myFeedback)
                            <form method="POST" action="{{ route('feedback.destroy', $myFeedback) }}" class="mt-3" onsubmit="return confirm('Delete your review?')">@csrf @method('DELETE')<button type="submit" class="text-sm text-red-700 underline">Delete my review</button></form>
                        @endif
                        <p class="mt-4 text-sm"><a class="text-link" href="{{ route('complaints.create') }}">Something wrong with this product? Submit a complaint →</a></p>
                    @endif
                @else
                    <p class="text-sm text-stone-500"><a class="text-link" href="{{ route('login') }}">Log in</a> as a consumer to leave a review or submit a complaint about this product.</p>
                @endauth
            </div>
        </x-card>
    </div>
@endsection
