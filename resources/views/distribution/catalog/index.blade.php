@extends('layouts.front')
@section('title', 'Product catalog')
@section('content')
    <section class="mb-10 rounded-2xl bg-olive-800 px-6 py-10 text-cream sm:px-10 sm:py-14" aria-labelledby="catalog-heading">
        <p class="eyebrow mb-4 text-olive-100/70">OliveTrace / Public catalog</p>
        <h1 id="catalog-heading" class="display-title max-w-3xl text-4xl sm:text-5xl">Discover traceable Tunisian olive oils</h1>
        <p class="mt-5 max-w-2xl text-sm leading-7 text-olive-100/85 sm:text-base">Explore Tunisian olive oils and follow each product’s journey from farm to bottle.</p>
    </section>

    <section aria-label="Search and filter products">
        <x-card class="mb-8">
            <form method="GET" action="{{ route('catalog.index') }}" class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(12rem,2fr)_1fr_1.2fr_1fr_auto]">
                <div>
                    <label for="search" class="form-label">Search name or brand</label>
                    <input id="search" class="form-input mt-2 w-full" type="search" name="search" maxlength="120" value="{{ $filters['search'] ?? '' }}" placeholder="e.g. Chemlali">
                </div>
                <div>
                    <label for="bottle_volume_ml" class="form-label">Bottle volume</label>
                    <select id="bottle_volume_ml" name="bottle_volume_ml" class="mt-2 w-full">
                        <option value="">Any volume</option>
                        @foreach([250, 500, 750, 1000] as $volume)
                            <option value="{{ $volume }}" @selected((string) ($filters['bottle_volume_ml'] ?? '') === (string) $volume)>{{ $volume === 1000 ? '1 L' : $volume.' ml' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="delivered" class="form-label">Distribution</label>
                    <select id="delivered" name="delivered" class="mt-2 w-full">
                        <option value="">Any shipment status</option>
                        <option value="1" @selected(($filters['delivered'] ?? '') === '1')>Delivered products</option>
                    </select>
                </div>
                <div>
                    <label for="sort" class="form-label">Sort by</label>
                    <select id="sort" name="sort" class="mt-2 w-full">
                        <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                        <option value="name" @selected(($filters['sort'] ?? '') === 'name')>Name</option>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <button class="btn-primary" type="submit">Apply</button>
                    <a class="text-link whitespace-nowrap" href="{{ route('catalog.index') }}">Reset</a>
                </div>
            </form>
        </x-card>
    </section>

    @if($products->isEmpty())
        <x-card class="py-12 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-olive-50 text-olive-700"><x-icon name="leaf" class="h-7 w-7" /></span>
            <h2 class="mt-5 font-display text-3xl">No products match those filters.</h2>
            <p class="mt-3 text-sm text-stone-500">Try another search or clear the filters to explore all available products.</p>
            <a class="btn-primary mt-6" href="{{ route('catalog.index') }}">Reset filters</a>
        </x-card>
    @else
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <h2 class="font-display text-2xl">Olive oils to discover</h2>
            <p class="text-sm text-stone-500">{{ $products->total() }} product(s)</p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach($products as $card)
                @php
                    $product = $card['product'];
                    $verified = in_array($card['certificationStatus'], ['valid', 'verified', 'certified'], true);
                    $certificationLabel = $verified
                        ? 'Verified'
                        : (in_array($card['certificationStatus'], ['pending', 'not verified'], true) ? 'Certification pending' : 'Certification not available');
                @endphp
                <article class="surface-card flex flex-col overflow-hidden p-0">
                    <div class="aspect-[4/3] overflow-hidden bg-olive-50">
                        @if($card['imageUrl'])
                            <img src="{{ $card['imageUrl'] }}" alt="{{ $product->name }} olive oil bottle" loading="lazy" class="h-full w-full object-cover" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                            <svg class="h-full w-full" viewBox="0 0 480 360" role="img" aria-label="Olive branch illustration placeholder" hidden>
                                <rect width="480" height="360" fill="#eef2e8" />
                                <path d="M170 246c34-58 93-93 153-115" fill="none" stroke="#586a3d" stroke-width="8" stroke-linecap="round" />
                                <path d="M231 205c-37-42-82-43-103-22 7 39 53 61 103 22Zm42-35c-5-51 21-84 53-91 22 34 4 79-53 91Zm5 42c32-32 74-34 98-14-7 36-48 53-98 14Zm-71 46c-36-31-78-28-98-5 10 35 52 50 98 5Z" fill="#738352" />
                                <circle cx="251" cy="215" r="9" fill="#b59459" /><circle cx="308" cy="154" r="9" fill="#b59459" />
                            </svg>
                        @else
                            <svg class="h-full w-full" viewBox="0 0 480 360" role="img" aria-label="Olive branch illustration placeholder">
                                <rect width="480" height="360" fill="#eef2e8" />
                                <path d="M170 246c34-58 93-93 153-115" fill="none" stroke="#586a3d" stroke-width="8" stroke-linecap="round" />
                                <path d="M231 205c-37-42-82-43-103-22 7 39 53 61 103 22Zm42-35c-5-51 21-84 53-91 22 34 4 79-53 91Zm5 42c32-32 74-34 98-14-7 36-48 53-98 14Zm-71 46c-36-31-78-28-98-5 10 35 52 50 98 5Z" fill="#738352" />
                                <circle cx="251" cy="215" r="9" fill="#b59459" /><circle cx="308" cy="154" r="9" fill="#b59459" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-center justify-between gap-3">
                            <p class="eyebrow min-w-0 text-olive-600">{{ $product->brand }}</p>
                            <span class="role-badge shrink-0">{{ $product->bottle_volume_ml === 1000 ? '1 L' : $product->bottle_volume_ml.' ml' }}</span>
                        </div>
                        <h2 class="mt-2 line-clamp-2 font-display text-2xl leading-tight [overflow-wrap:anywhere]" title="{{ $product->name }}">{{ $product->name }}</h2>
                        <p class="mt-4 text-xs text-stone-500">Packaged {{ $product->packaging_date->format('d M Y') }}</p>
                        <p class="mt-3 text-sm leading-6 text-stone-600">
                            @if($card['lot'])
                                {{ $card['lot']->grade }} · Tunisian origin · Lot {{ $card['lot']->lotCode }}
                            @else
                                Tunisian olive oil · Origin details not available
                            @endif
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="role-badge" @if($verified) aria-label="Certification verified" @else aria-label="{{ $certificationLabel }}" @endif>{{ $certificationLabel }}</span>
                            @if($card['deliveredDestination'])
                                <span class="role-badge">Delivered to {{ $card['deliveredDestination'] }}</span>
                            @endif
                        </div>
                        @if($card['rating'])
                            <p class="mt-4 text-sm text-stone-600" aria-label="{{ number_format($card['rating']->average, 1) }} out of 5 from {{ $card['rating']->count }} ratings">
                                <span class="text-gold" aria-hidden="true">★★★★★</span>
                                <span class="ml-1 font-semibold">{{ number_format($card['rating']->average, 1) }}</span>
                                <span>({{ $card['rating']->count }})</span>
                            </p>
                        @endif
                        <div class="mt-auto pt-6">
                            @if($traceRouteAvailable)
                                <a class="btn-primary w-full" href="{{ $product->traceUrl() }}">View traceability <x-icon name="arrow" /></a>
                            @else
                                <button class="btn-secondary w-full cursor-not-allowed" type="button" disabled aria-disabled="true">Traceability page coming soon</button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <nav class="mt-9" aria-label="Product catalog pages">{{ $products->links() }}</nav>
    @endif
@endsection
