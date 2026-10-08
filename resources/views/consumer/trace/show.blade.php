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
        $co2Total = $timeline['co2_total_kg'];
        $feedback = $timeline['feedback'];
        $myFeedback = auth()->check() ? $feedback['items']->firstWhere('consumer_user_id', auth()->id()) : null;
        $bottle = ((int) ($product->bottle_volume_ml ?? 0)) === 1000 ? '1 L' : ($product->bottle_volume_ml ?? '—').(is_numeric($product->bottle_volume_ml ?? null) ? ' ml' : '');
        $packaged = $product->packaging_date instanceof \DateTimeInterface ? $product->packaging_date->format('d M Y') : ($product->packaging_date ?? '—');
        $certStatus = $verification && $verification['certificate'] ? strtolower(trim($verification['certificate']->status ?? $verification['request']->status ?? '')) : '';
        $verified = $verification && $verification['certificate'] && ! $verification['expired'] && in_array($certStatus, ['valid', 'verified', 'certified'], true);
        $verificationBadge = ! $verification ? 'No certificate available' : ($verification['certificate'] ? ($verification['expired'] ? 'Expired' : ($verified ? 'Verified' : 'Certificate issued')) : 'Certification pending');
        $delivered = collect($shipments)->first(fn ($shipment) => ($shipment->status ?? '') === 'delivered');
    @endphp
    <div class="mx-auto max-w-3xl">
        <p class="eyebrow mb-4 text-olive-600">From farm to consumer</p>
        <h1 class="display-title mb-4 text-4xl sm:text-5xl">Follow this bottle.</h1>
        <p class="mb-8 text-sm leading-7 text-stone-500">Every step below is read from the module that recorded it. Nothing is copied or rewritten here.</p>

        <article class="surface-card mb-10 overflow-hidden p-0" aria-label="Product identity">
            <div class="aspect-[16/7] overflow-hidden bg-olive-50">
                @if($timeline['image_url'])
                    <img src="{{ $timeline['image_url'] }}" alt="{{ $product->name ?? 'Olive oil product' }} bottle" class="hero-image">
                @else
                    <div class="grid h-full place-items-center"><x-icon name="leaf" class="h-14 w-14 text-olive-600/30" /></div>
                @endif
            </div>
            <div class="p-6 sm:p-8">
                <div class="flex items-center justify-between gap-3">
                    <p class="eyebrow min-w-0 text-olive-600">{{ $product->brand ?? 'Tunisian olive oil' }}</p>
                    <span class="role-badge shrink-0">{{ $bottle }}</span>
                </div>
                <h2 class="font-display mt-3 text-3xl sm:text-4xl">{{ $product->name ?? 'Olive oil product #'.$product->id }}</h2>
                <dl class="mt-6 grid gap-5 border-t border-stone-100 pt-6 text-sm sm:grid-cols-3">
                    <div><dt class="text-stone-500">Packaged</dt><dd class="mt-1 font-semibold">{{ $packaged }}</dd></div>
                    <div><dt class="text-stone-500">Oil lot</dt><dd class="mt-1 font-semibold">{{ $oilLot->lot_code ?? '—' }}</dd></div>
                    <div><dt class="text-stone-500">Olive variety</dt><dd class="mt-1 font-semibold">{{ $origin['olive_variety'] ?? '—' }}</dd></div>
                </dl>
                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <span class="role-badge">{{ $verificationBadge }}</span>
                    @if($delivered)<span class="role-badge">Delivered to {{ $delivered->destination }}</span>@endif
                </div>
                @if($feedback['average'])
                    <div class="mt-4 border-t border-stone-100 pt-4"><x-rating-stars :value="$feedback['average']" :count="$feedback['count']" /></div>
                @endif
            </div>
        </article>

        <div class="space-y-10">
            <section aria-label="Producer and farm origin">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">1</span><p class="eyebrow text-olive-600">Step 1 · Producer and farm origin</p></div>
                @if($origin)
                    <x-origin-card :origin="$origin" />
                @else
                    <x-card><h2 class="font-display text-2xl">Origin not published yet.</h2><p class="mt-3 text-sm text-stone-500">The producer has not published this farm's origin information.</p></x-card>
                @endif
            </section>

            <section aria-label="Harvest, mill and oil lot">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">2</span><p class="eyebrow text-olive-600">Step 2 · Harvest, mill and oil lot</p></div>
                <x-card>
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
            </section>

            <section aria-label="Laboratory verification">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">3</span><p class="eyebrow text-olive-600">Step 3 · Laboratory verification</p></div>
                <x-card>
                    @if($verification && $verification['certificate'])
                        @php $cert = $verification['certificate']; @endphp
                        <p class="flex flex-wrap items-center gap-3"><span class="role-badge">{{ $verificationBadge }}</span><span class="font-display text-2xl">{{ $cert->certificate_number ?? 'Certificate' }}</span></p>
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
            </section>

            <section aria-label="Distribution journey">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">4</span><p class="eyebrow text-olive-600">Step 4 · Distribution journey</p></div>
                <x-card>
                    @forelse($shipments as $shipment)
                        <div class="border-t border-stone-100 py-4 first:border-0 first:pt-0">
                            <p class="flex flex-wrap items-center justify-between gap-2 text-sm font-semibold"><span>{{ $shipment->departure_location ?? '—' }} → {{ $shipment->destination ?? '—' }}</span><span class="role-badge">{{ $shipment->status ?? '' }}</span></p>
                            <p class="mt-1 text-xs text-stone-500">{{ $shipment->departure_date ?? '' }} @if(!empty($shipment->arrival_date)) · arrived {{ $shipment->arrival_date }} @endif · {{ $shipment->transport_type ?? '' }} @if(isset($shipment->distance_km)) · {{ $shipment->distance_km }} km @endif @if(isset($shipment->co2_estimate) && ($shipment->status ?? '') !== 'cancelled') · CO₂ {{ $shipment->co2_estimate }} kg @endif</p>
                        </div>
                    @empty
                        <p class="text-sm text-stone-500">No shipment recorded for this product yet.</p>
                    @endforelse
                    @if($co2Total)
                        <p class="mt-4 border-t border-stone-100 pt-4 text-xs leading-5 text-stone-500">Estimated transport footprint: <span class="font-semibold text-stone-700">{{ number_format($co2Total, 2) }} kg CO₂</span>, calculated from the distributor's shipment records. Transport facts are never rewritten here.</p>
                    @endif
                </x-card>
            </section>

            <section aria-label="Consumer reviews">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">5</span><p class="eyebrow text-olive-600">Step 5 · Consumer reviews</p></div>
                <x-card>
                    @if($feedback['average'])
                        <div class="mb-5"><x-rating-stars :value="$feedback['average']" :count="$feedback['count']" /></div>
                    @endif
                    @forelse($feedback['items'] as $item)
                        <div class="border-t border-stone-100 py-4 first:border-0 first:pt-0">
                            <div class="flex flex-wrap items-center justify-between gap-2"><x-rating-stars :value="$item->rating" /><span class="text-xs text-stone-500">{{ $item->consumer?->name ?? 'Consumer' }} · {{ $item->created_at?->toDateString() }}</span></div>
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
            </section>
        </div>
    </div>
@endsection
