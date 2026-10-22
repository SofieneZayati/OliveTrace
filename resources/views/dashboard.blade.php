@extends(auth()->user()->role === \App\Enums\Role::Consumer ? 'layouts.front' : 'layouts.admin')
@section('title', 'Dashboard')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="eyebrow mb-4 text-olive-600">OliveTrace / Your dashboard</p>
            <h1 class="display-title text-4xl sm:text-5xl">Welcome, {{ auth()->user()->name }}.</h1>
            <p class="mt-4 text-sm text-stone-500">A shared space. A fresh beginning.</p>
        </div>
        <span class="role-badge">Role: {{ auth()->user()->role->label() }}</span>
    </div>
    <section class="relative mb-7 overflow-hidden rounded-2xl bg-olive-800 p-7 text-cream sm:p-10"
        aria-labelledby="workspace-heading">
        <x-icon name="leaf" class="pointer-events-none absolute -right-6 -top-8 h-64 w-64 rotate-12 text-cream/5" />
        <div class="relative max-w-xl">
            <p class="eyebrow mb-4 text-olive-100/70">Growing together</p>
            @if(auth()->user()->role === \App\Enums\Role::Producer)
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Your origin starts here.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Build your producer profile, manage your farms, record
                    harvests, and request certifications.</p>
                <div class="flex flex-wrap gap-4 mt-6">
                    <a href="{{ route('producer.farms.index') }}" class="btn-secondary">Explore my farms <x-icon
                            name="arrow" /></a>
                    <a href="{{ route('certification.producer.requests.index') }}" class="btn-secondary">My Certificate Requests
                        <x-icon name="arrow" /></a>
                </div>
            @elseif(auth()->user()->role === \App\Enums\Role::Laboratory)
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Verify the origin.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Process certification requests, analyze lab results, and
                    issue official certificates to producers.</p>
                <a href="{{ route('lab.requests.index') }}" class="btn-secondary mt-6">View Certification Requests <x-icon
                        name="arrow" /></a>
            @elseif(auth()->user()->isAdmin())
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Look after the origin story.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Inspect producer profiles, farms and certificates, correct
                    details and keep invalid origin information out of public view.</p>
                <a href="{{ route('admin.farms.index') }}" class="btn-secondary mt-6">Explore community farms <x-icon
                        name="arrow" /></a>
            @elseif(auth()->user()->role === \App\Enums\Role::Miller)
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Your mill is part of the story.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Keep your huilerie details accurate: region, extraction
                    method and hourly capacity.</p>
                <a href="{{ route('mill.show') }}" class="btn-secondary mt-6">Manage my mill <x-icon name="arrow" /></a>
            @elseif(auth()->user()->role === \App\Enums\Role::Consumer)
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Bienvenue, consommateur engagé.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Tracez vos bouteilles, partagez vos avis et suivez vos réclamations.</p>
                <div class="flex flex-wrap gap-4 mt-6">
                    <a href="{{ route('trace.search') }}" class="btn-secondary">🔍 Tracer un produit <x-icon name="arrow" /></a>
                    <a href="{{ route('complaints.index') }}" class="btn-secondary">📋 Mes réclamations <x-icon name="arrow" /></a>
                </div>
            @else
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Your workspace is taking root.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">The business modules will appear here.</p>
                <p class="mt-1 text-sm leading-7 text-olive-100/80">For now, your account and shared tools are ready.</p>
            @endif
        </div>
    </section>
    <div class="grid gap-5 md:grid-cols-2">
        @if($metrics !== null)
            <x-card class="md:col-span-2">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow text-olive-600">Distribution overview</p>
                        <h2 class="mt-2 font-display text-3xl">Your distribution at a glance.</h2>
                        <p class="mt-3 text-sm leading-6 text-stone-500">
                            {{ auth()->user()->isAdmin() ? 'All distributor shipments across OliveTrace.' : 'Only shipments linked to your distributor profile.' }}
                        </p>
                    </div>
                    <a class="btn-secondary" href="{{ route(auth()->user()->isAdmin() ? 'admin.shipments.index' : 'distributor.shipments.index') }}">View shipments</a>
                </div>
                <form method="GET" action="{{ route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard') }}" class="mb-6 grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_auto]">
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
                        <a class="text-link" href="{{ route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard') }}">Reset</a>
                    </div>
                </form>
                @php
                    $hasShipments = array_sum($metrics['status_counts']) > 0;
                @endphp
                @if(! $hasShipments)
                    <p class="mb-6 rounded-xl bg-olive-50 p-4 text-sm text-stone-600">No shipments in this period.</p>
                @endif
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-stone-100 p-4"><p class="text-sm text-stone-500">Total distance</p><p class="mt-2 font-display text-2xl">{{ number_format($metrics['total_distance'], 0) }} km</p></div>
                    <div class="rounded-xl border border-stone-100 p-4"><p class="text-sm text-stone-500">Estimated CO₂</p><p class="mt-2 font-display text-2xl">{{ number_format($metrics['total_co2'], 2) }} kg</p></div>
                    <div class="rounded-xl border border-stone-100 p-4"><p class="text-sm text-stone-500">Average delivery</p><p class="mt-2 font-display text-2xl">{{ $metrics['average_delivery_days'] === null ? '—' : number_format($metrics['average_delivery_days'], 1).' days' }}</p></div>
                    <div class="rounded-xl border border-stone-100 p-4"><p class="text-sm text-stone-500">Overdue shipments</p><p class="mt-2 font-display text-2xl">{{ $metrics['overdue_count'] }}</p></div>
                </div>
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="font-display text-2xl">Shipment status</h3>
                        <div class="mt-4 space-y-3">
                            @foreach(['planned' => 'Planned', 'in_transit' => 'In transit', 'delivered' => 'Delivered'] as $status => $label)
                                @php
    $count = $metrics['status_counts'][$status] ?? 0;
@endphp
                                <div>
                                    <div class="flex justify-between text-sm"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                                    <div class="mt-1 h-2 rounded-full bg-olive-100"><div class="h-2 rounded-full bg-olive-600" style="width: {{ $hasShipments ? ($count / array_sum($metrics['status_counts'])) * 100 : 0 }}%" role="img" aria-label="{{ $label }}: {{ $count }}"></div></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h3 class="font-display text-2xl">Transport types</h3>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse($metrics['transport_counts'] as $type => $count)
                                <span class="role-badge">{{ ucfirst(str_replace('_', ' ', $type)) }}: {{ $count }}</span>
                            @empty
                                <span class="text-sm text-stone-500">No transport data.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </x-card>
        @endif
        <x-card>
            <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                    name="user" /></div>
            <h2 class="text-lg font-semibold">Make yourself at home</h2>
            <p class="mb-6 mt-2 text-sm leading-6 text-stone-500">Keep your personal details up to date and manage your
                account security.</p>
            <a href="{{ route('profile.edit') }}" class="text-link inline-flex items-center gap-2">Manage profile <x-icon
                    name="arrow" class="h-4 w-4" /></a>
        </x-card>
        @can('access-admin')
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                        name="users" /></div>
                <h2 class="text-lg font-semibold">Look after the community</h2>
                <p class="mb-6 mt-2 text-sm leading-6 text-stone-500">Manage shared accounts, assign roles, and control who can
                    access OliveTrace.</p>
                <a href="{{ route('admin.users.index') }}" class="text-link inline-flex items-center gap-2">Manage users <x-icon
                        name="arrow" class="h-4 w-4" /></a>
            </x-card>
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                        name="drop" /></div>
                <h2 class="text-lg font-semibold">Keep the mill directory accurate</h2>
                <p class="mb-6 mt-2 text-sm leading-6 text-stone-500">Review every huilerie on OliveTrace, its region,
                    extraction method and hourly capacity.</p>
                <a href="{{ route('admin.mills.index') }}" class="text-link inline-flex items-center gap-2">Browse mills <x-icon
                        name="arrow" class="h-4 w-4" /></a>
            </x-card>
        @elseif(auth()->user()->role === \App\Enums\Role::Miller)
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                        name="drop" /></div>
                @if (auth()->user()->mill)
                    <h2 class="text-lg font-semibold">{{ auth()->user()->mill->name }}</h2>
                    <p class="mb-6 mt-2 text-sm leading-6 text-stone-500">{{ auth()->user()->mill->region }} ·
                        {{ ucwords(str_replace('_', ' ', auth()->user()->mill->extraction_type)) }} ·
                        {{ auth()->user()->mill->capacity ? number_format(auth()->user()->mill->capacity) . ' kg/h' : 'capacity not set' }}.
                        Keep it accurate so the OliveTrace story stays true.</p>
                    <a href="{{ route('mill.show') }}" class="text-link inline-flex items-center gap-2">Manage my mill <x-icon
                            name="arrow" class="h-4 w-4" /></a>
                @else
                    <h2 class="text-lg font-semibold">Your mill is not linked yet</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-500">No huilerie is attached to this account. Ask an administrator
                        to create it.</p>
                @endif
            </x-card>
        @else
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                        name="check" /></div>
                <h2 class="text-lg font-semibold">Your place in OliveTrace</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500">You're signed in as a
                    {{ strtolower(auth()->user()->role->label()) }}. Your role helps us bring the right workspace to you.</p>
                <p class="mt-6 inline-flex items-center gap-2 text-xs font-semibold text-olive-700"><span
                        class="h-1.5 w-1.5 rounded-full bg-olive-500"></span>Account active</p>
            </x-card>
        @endcan
        @if(auth()->user()->role === \App\Enums\Role::Consumer)
            {{-- Feature 3: Consumer Dashboard Widgets --}}
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon name="star" /></div>
                <h2 class="text-lg font-semibold">Mes avis</h2>
                @if($myReviews->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach($myReviews as $review)
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span class="truncate">{{ $review->product?->name ?? 'Produit #'.$review->oil_product_id }}</span>
                                <span class="shrink-0 font-semibold text-gold">{{ str_repeat('★', $review->rating) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-2 text-sm text-stone-500">Aucun avis publié. Tracez un produit pour laisser votre premier avis.</p>
                @endif
            </x-card>
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon name="check" /></div>
                <h2 class="text-lg font-semibold">Mes réclamations</h2>
                @if($myComplaints->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach($myComplaints as $c)
                            @php $dot = ['open' => 'bg-gold', 'in_review' => 'bg-olive-500', 'resolved' => 'bg-olive-800', 'rejected' => 'bg-stone-300'][$c->status->value] ?? 'bg-stone-300'; @endphp
                            <a href="{{ route('complaints.show', $c) }}" class="flex items-center justify-between gap-2 text-sm hover:text-olive-600">
                                <span class="flex items-center gap-2 truncate"><span class="h-2 w-2 rounded-full {{ $dot }}"></span>{{ $c->subject }}</span>
                                <span class="role-badge shrink-0">{{ $c->status->label() }}</span>
                            </a>
                        @endforeach
                    </div>
                    <a class="text-link mt-4 inline-flex items-center gap-2" href="{{ route('complaints.index') }}">Voir toutes <x-icon name="arrow" class="h-4 w-4" /></a>
                @else
                    <p class="mt-2 text-sm text-stone-500">Aucune réclamation. C'est bon signe !</p>
                @endif
            </x-card>
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon name="leaf" /></div>
                <h2 class="text-lg font-semibold">Produits les mieux notés</h2>
                @if($topProducts->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach($topProducts as $tp)
                            @php $tpProduct = $tp->product; @endphp
                            @if($tpProduct)
                                <a href="{{ route('trace.show', $tpProduct->slug ?? $tpProduct->id) }}" class="flex items-center justify-between gap-2 text-sm hover:text-olive-600">
                                    <span class="truncate">{{ $tpProduct->name }}</span>
                                    <span class="shrink-0 font-semibold text-gold">{{ round($tp->avg_rating, 1) }} ★ <span class="font-normal text-stone-500">({{ $tp->review_count }})</span></span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="mt-2 text-sm text-stone-500">Pas encore assez d'avis pour un classement.</p>
                @endif
            </x-card>
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon name="drop" /></div>
                <h2 class="text-lg font-semibold">Tracer un produit</h2>
                <p class="mt-2 text-sm text-stone-500">Scannez le QR code ou entrez le code produit pour vérifier la traçabilité.</p>
                <a href="{{ route('trace.search') }}" class="text-link mt-4 inline-flex items-center gap-2">🔍 Ouvrir le scanner <x-icon name="arrow" class="h-4 w-4" /></a>
            </x-card>
        @endif
    </div>
@endsection
