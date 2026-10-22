@extends('layouts.front')
@section('title', 'Product traceability')
@section('content')
    @php
        $product = $timeline['product'];
        $origin = $timeline['origin'];
        $harvest = $timeline['harvest'];
        $mill = $timeline['mill'];
        $milling = $timeline['milling'];
        $oilLot = $timeline['oil_lot'];
        $verification = $timeline['verification'];
        $shipments = $timeline['shipments'];
        $co2Total = $timeline['co2_total_kg'];
        $feedback = $timeline['feedback'];
        $myFeedback = auth()->check() ? $feedback['items']->firstWhere('consumer_user_id', auth()->id()) : null;
        $bottle = ((int) ($product->bottle_volume_ml ?? 0)) === 1000 ? '1 L' : ($product->bottle_volume_ml ?? '—').(is_numeric($product->bottle_volume_ml ?? null) ? ' ml' : '');
        $packaged = $product->packaging_date instanceof \DateTimeInterface ? $product->packaging_date->format('d M Y') : ($product->packaging_date ?? '—');
        $verified = $verification['verified'] ?? false;
        $verificationBadge = ! $verification ? 'No certificate available' : ($verification['certificate'] ? ($verification['expired'] ? 'Expired' : ($verified ? 'Valid certificate' : 'Certificate issued')) : 'Certification pending');
        $delivered = collect($shipments)->first(fn ($shipment) => ($shipment->status ?? '') === 'delivered');
        $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : '—';
    @endphp
    <div class="mx-auto max-w-6xl">
        <p class="eyebrow mb-4 text-olive-600">From farm to consumer</p>
        <h1 class="display-title mb-4 text-4xl sm:text-5xl">Follow this bottle.</h1>
        <p class="mb-8 text-sm leading-7 text-stone-500">Discover the origin, production and journey of this olive oil, with the records available for each step.</p>

        <article class="surface-card mb-8 overflow-hidden p-0 md:grid md:grid-cols-2" aria-label="Product identity">
            <div class="aspect-[4/3] overflow-hidden bg-olive-50 md:aspect-auto md:min-h-80">
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
                <a class="text-link mt-4 inline-block" href="{{ route('trace.share', $product->slug) }}">Share this product and QR code</a>
                @if($product->description)
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-stone-600">{{ $product->description }}</p>
                @endif
                <dl class="mt-6 grid gap-5 border-t border-stone-100 pt-6 text-sm sm:grid-cols-3">
                    <div><dt class="text-stone-500">Packaged</dt><dd class="mt-1 font-semibold">{{ $packaged }}</dd></div>
                    <div><dt class="text-stone-500">Oil lot</dt><dd class="mt-1 font-semibold">{{ $oilLot->lot_number ?? '—' }}</dd></div>
                    <div><dt class="text-stone-500">Olive variety</dt><dd class="mt-1 font-semibold">{{ $origin['olive_variety'] ?? '—' }}</dd></div>
                </dl>
                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <span class="trust-badge {{ $verified ? 'trust-verified' : (($verification['expired'] ?? false) ? 'trust-expired' : 'trust-neutral') }}">{{ $verificationBadge }}</span>
                    @if($delivered)<span class="role-badge">Delivered to {{ $delivered->destination }}</span>@endif
                </div>
                @if($feedback['average'])
                    <div class="mt-4 border-t border-stone-100 pt-4"><x-rating-stars :value="$feedback['average']" :count="$feedback['count']" /></div>
                @else
                    <p class="mt-4 text-sm text-stone-500">No reviews yet.</p>
                @endif
                <div class="mt-6 flex flex-wrap items-center gap-4">
                    @if(auth()->guest())
                        <a class="btn-primary" href="{{ route('login') }}">Log in to leave a review</a>
                    @else
                        <a class="btn-primary" href="#reviews">{{ auth()->user()->role === \App\Enums\Role::Consumer ? ($myFeedback ? 'Update your review' : 'Leave a review') : 'Read reviews' }}</a>
                    @endif
                    @if(!empty($verification['document_url']))
                        <a class="text-link" href="{{ $verification['document_url'] }}" target="_blank" rel="noopener">View certificate document →</a>
                    @endif
                </div>
            </div>
        </article>

        <nav class="story-nav mb-8 flex flex-wrap gap-2 rounded-2xl border border-stone-200 bg-white p-3" aria-label="Product story sections">
            <a href="#origin">1 · Origin</a><a href="#production">2 · Production</a><a href="#verification">3 · Verification</a><a href="#distribution">4 · Distribution</a><a href="#reviews">5 · Reviews ({{ $feedback['count'] }})</a>
        </nav>
        <div class="space-y-10">
            <section id="origin" class="story-section" aria-label="Producer and farm origin">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">1</span><p class="eyebrow text-olive-600">Step 1 · Producer and farm origin</p></div>
                @if($origin)
                    <x-origin-card :origin="$origin" />
                    @if($timeline['origin_map'])
                        <details class="mt-4 rounded-2xl border border-stone-200 bg-white p-5">
                            <summary class="text-sm font-semibold">Explore the origin region on the map</summary>
                            <div class="mt-4"><x-farm-map :points="$timeline['origin_map']" :public-map="true" /></div>
                        </details>
                    @endif
                @else
                    <x-card><h2 class="font-display text-2xl">Origin not published yet.</h2><p class="mt-3 text-sm text-stone-500">The producer has not published this farm's origin information.</p></x-card>
                @endif
            </section>

            <section id="production" class="story-section" aria-label="Harvest, mill and oil lot">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">2</span><p class="eyebrow text-olive-600">Step 2 · Harvest, mill and oil lot</p></div>
                <x-card>
                    @if($harvest)
                        <dl class="grid gap-5 text-sm sm:grid-cols-3">
                            <div><dt class="text-stone-500">Harvest date</dt><dd class="mt-1 font-semibold">{{ $date($harvest->harvest_date ?? null) }}</dd></div>
                            <div><dt class="text-stone-500">Method</dt><dd class="mt-1 font-semibold">{{ \App\Enums\HarvestMethod::tryFrom($harvest->method ?? '')?->label() ?? 'Not specified' }}</dd></div>
                            <div><dt class="text-stone-500">Quantity</dt><dd class="mt-1 font-semibold">{{ isset($harvest->quantity_kg) ? $harvest->quantity_kg.' kg' : '—' }}</dd></div>
                        </dl>
                    @else
                        <p class="text-sm text-stone-500">No harvest information available for this product yet.</p>
                    @endif
                    <div class="mt-5 border-t border-stone-100 pt-5">
                        @if($mill && isset($mill->mill))
                            <p class="text-sm"><span class="font-semibold">{{ $mill->mill->name ?? 'Registered mill' }}</span> <span class="text-stone-500">· {{ $mill->mill->region ?? '' }}</span></p>
                        @elseif($mill && !empty($mill->external))
                            <p class="text-sm"><span class="font-semibold">{{ $mill->external }}</span> <span class="text-stone-500">(external mill)</span></p>
                        @else
                            <p class="text-sm text-stone-500">No mill information available for this product yet.</p>
                        @endif
                        @if($oilLot)
                            <dl class="mt-4 grid gap-5 text-sm sm:grid-cols-4">
                                <div><dt class="text-stone-500">Lot code</dt><dd class="mt-1 font-semibold">{{ $oilLot->lot_number ?? '—' }}</dd></div>
                                <div><dt class="text-stone-500">Extraction</dt><dd class="mt-1 font-semibold">{{ $date($oilLot->production_date ?? null) }}</dd></div>
                                <div><dt class="text-stone-500">Volume</dt><dd class="mt-1 font-semibold">{{ isset($oilLot->liters) ? $oilLot->liters.' L' : '—' }}</dd></div>
                                <div><dt class="text-stone-500">Grade · Acidity</dt><dd class="mt-1 font-semibold">{{ isset($oilLot->quality_grade) ? \App\Enums\OilQuality::tryFrom($oilLot->quality_grade)?->label() ?? '—' : '—' }} · {{ $verification['analysis']->acidity ?? '—' }}</dd></div>
                            </dl>
                        @endif
                        @if($milling)
                            <h3 class="mt-6 font-semibold">Completed milling · {{ $date($milling['completed_at'] ?? null) }}</h3>
                            <dl class="mt-4 grid gap-5 text-sm sm:grid-cols-4">
                                <div><dt class="text-stone-500">Olives received</dt><dd class="mt-1 font-semibold">{{ $milling['received_quantity_kg'] ?? '—' }} kg</dd></div>
                                <div><dt class="text-stone-500">Oil produced</dt><dd class="mt-1 font-semibold">{{ $milling['output_volume_l'] ?? '—' }} L</dd></div>
                                <div><dt class="text-stone-500">Extraction rate</dt><dd class="mt-1 font-semibold">{{ $milling['extraction_rate'] ?? '—' }}%</dd></div>
                                <div><dt class="text-stone-500">Olive condition</dt><dd class="mt-1 font-semibold">{{ \App\Enums\Production\OliveCondition::tryFrom($milling['olive_condition'] ?? '')?->label() ?? '—' }}</dd></div>
                            </dl>
                            <p class="mt-3 text-xs text-stone-500">Extraction rate describes production yield; it is not a quality score.</p>
                        @endif
                    </div>
                </x-card>
            </section>

            <section id="verification" class="story-section" aria-label="Laboratory verification">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">3</span><p class="eyebrow text-olive-600">Step 3 · Laboratory verification</p></div>
                <x-card>
                    @if($verification && $verification['certificate'])
                        @php $cert = $verification['certificate']; @endphp
                        <p class="flex flex-wrap items-center gap-3"><span class="role-badge">{{ $verificationBadge }}</span><span class="font-display text-2xl">{{ $cert->certificate_number ?? 'Certificate' }}</span></p>
                        @if(!empty($verification['document_url']))<p class="mt-3 text-sm"><a class="text-link" href="{{ $verification['document_url'] }}" target="_blank" rel="noopener">View certificate document →</a></p>@endif
                        <dl class="mt-4 grid gap-5 text-sm sm:grid-cols-3">
                            <div><dt class="text-stone-500">Type</dt><dd class="mt-1 font-semibold">{{ $cert->type ?? '—' }}</dd></div>
                            <div><dt class="text-stone-500">Issued</dt><dd class="mt-1 font-semibold">{{ $date($cert->issue_date ?? null) }}</dd></div>
                            <div><dt class="text-stone-500">Valid until</dt><dd class="mt-1 font-semibold">{{ $date($cert->expiry_date ?? null) }}</dd></div>
                        </dl>
                    @elseif($verification)
                        <p class="text-sm text-stone-500">Verification request status: <span class="font-semibold">{{ $verification['request']->status ?? 'pending' }}</span>. No certificate issued yet.</p>
                    @else
                        <p class="text-sm text-stone-500">No certificate available for this product yet.</p>
                    @endif
                    @if($verification && $verification['analysis'])
                        @php $analysis = $verification['analysis']; @endphp
                        <h3 class="mt-6 border-t border-stone-100 pt-5 font-semibold">Laboratory analysis</h3>
                        <dl class="mt-4 grid gap-5 text-sm sm:grid-cols-4">
                            <div><dt class="text-stone-500">Analysis date</dt><dd class="mt-1 font-semibold">{{ $date($analysis->analysis_date ?? null) }}</dd></div>
                            <div><dt class="text-stone-500">Recorded result</dt><dd class="mt-1 font-semibold">{{ $analysis->result === null ? 'Not recorded' : ($analysis->result ? 'Passed' : 'Failed') }}</dd></div>
                            <div><dt class="text-stone-500">Acidity</dt><dd class="mt-1 font-semibold">{{ isset($analysis->acidity) ? $analysis->acidity.'%' : '—' }}</dd></div>
                            <div><dt class="text-stone-500">Peroxide value</dt><dd class="mt-1 font-semibold">{{ isset($analysis->peroxide_value) ? $analysis->peroxide_value.' meq O₂/kg' : '—' }}</dd></div>
                        </dl>
                    @endif
                </x-card>
            </section>

            <section id="distribution" class="story-section" aria-label="Distribution journey">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">4</span><p class="eyebrow text-olive-600">Step 4 · Distribution journey</p></div>
                <x-card>
                    @forelse($shipments as $shipment)
                        <div class="border-t border-stone-100 py-4 first:border-0 first:pt-0">
                            <p class="flex flex-wrap items-center justify-between gap-2 text-sm font-semibold"><span>{{ $shipment->departure_location ?? '—' }} → {{ $shipment->destination ?? '—' }}</span><span class="role-badge">{{ \App\Enums\ShipmentStatus::tryFrom($shipment->status ?? '')?->label() ?? 'Not specified' }}</span></p>
                            <p class="mt-1 text-xs text-stone-500">{{ $date($shipment->departure_date ?? null) }} @if(!empty($shipment->arrival_date)) · {{ ($shipment->status ?? '') === 'delivered' ? 'arrived' : 'arrival' }} {{ $date($shipment->arrival_date) }} @endif · {{ \App\Enums\TransportType::tryFrom($shipment->transport_type ?? '')?->label() ?? 'Not specified' }} @if(isset($shipment->distance_km)) · {{ $shipment->distance_km }} km @endif @if(isset($shipment->co2_estimate) && ($shipment->status ?? '') !== 'cancelled') · CO₂ {{ $shipment->co2_estimate }} kg @endif</p>
                        </div>
                    @empty
                        <p class="text-sm text-stone-500">No shipment recorded for this product yet.</p>
                    @endforelse
                    @if($co2Total)
                        <p class="mt-4 border-t border-stone-100 pt-4 text-xs leading-5 text-stone-500">Estimated transport footprint: <span class="font-semibold text-stone-700">{{ number_format($co2Total, 2) }} kg CO₂</span>, calculated from the distributor's shipment records. Transport facts are never rewritten here.</p>
                    @endif
                </x-card>
            </section>

            <section id="reviews" class="story-section" aria-label="Consumer reviews">
                <div class="mb-4 flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-800 font-display text-base text-cream">5</span><p class="eyebrow text-olive-600">Step 5 · Consumer reviews</p></div>
                <x-card>
                    @if($feedback['average'])
                        <div class="mb-5"><x-rating-stars :value="$feedback['average']" :count="$feedback['count']" /></div>
                    @endif
                    @forelse($feedback['items'] as $item)
                        <div class="border-t border-stone-100 py-4 first:border-0 first:pt-0">
                            <div class="flex flex-wrap items-center justify-between gap-2"><x-rating-stars :value="$item->rating" /><span class="text-xs text-stone-500">{{ $item->consumer?->name ?? 'Consumer' }} · {{ $item->created_at?->toDateString() }}</span></div>
                            @if($item->comment)<p class="mt-2 text-sm leading-6">{{ $item->comment }}</p>@endif
                            @if($item->hasVerifiedPurchase())<p class="mt-2 text-xs font-semibold text-olive-700">Verified purchase · proof reviewed by admin</p>@endif
                        </div>
                    @empty
                        <p class="text-sm text-stone-500">No reviews yet. Be the first to share your tasting.</p>
                    @endforelse
                    <div class="mt-6 border-t border-stone-100 pt-6">
                        @auth
                            @if(auth()->user()->role === \App\Enums\Role::Consumer)
                                <h3 class="font-display mb-4 text-2xl">{{ $myFeedback ? 'Update your review.' : 'Leave your review.' }}</h3>
                                <p class="mb-4 text-sm text-stone-500">Share your experience with the aroma, taste, finish or packaging.</p>
                                @include('consumer.feedback._form', ['feedback' => $myFeedback, 'productId' => $product->id])
                                @if($myFeedback)
                                    <form method="POST" action="{{ route('feedback.destroy', $myFeedback) }}" class="mt-3" onsubmit="return confirm('Delete your review?')">@csrf @method('DELETE')<button type="submit" class="text-sm text-red-700 underline">Delete my review</button></form>
                                @endif
                                <p class="mt-4 text-sm"><a class="text-link" href="{{ route('complaints.create') }}">Something wrong with this product? Submit a complaint →</a></p>
                                <details class="mt-6 text-sm"><summary>Submit a private purchase proof</summary>
                                    <form method="POST" action="{{ route('products.proof.upload', $product) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                                        @csrf
                                        <input type="hidden" name="proof_type" value="receipt">
                                        <label for="purchase-proof" class="block">Receipt (JPEG/PNG/PDF, maximum 5 MB)</label>
                                        <input id="purchase-proof" type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" class="block max-w-full">
                                        <x-primary-button>Submit for admin review</x-primary-button>
                                    </form>
                                </details>
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
