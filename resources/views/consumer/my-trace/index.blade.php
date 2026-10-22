@extends('layouts.front')
@section('title', 'Mon OliveTrace')
@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow mb-4 text-olive-600">Votre espace personnel</p>
                <h1 class="display-title text-4xl">Mon OliveTrace</h1>
                <p class="mt-4 text-sm text-stone-500">Tous vos produits, avis, réclamations et notifications en un coup d'œil.</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-10">
            <x-card class="p-5"><dt class="eyebrow text-olive-600">Avis donnés</dt><dd class="mt-2 font-display text-3xl">{{ $stats['total_reviews'] }}</dd></x-card>
            <x-card class="p-5"><dt class="eyebrow text-olive-600">Note moyenne</dt><dd class="mt-2 font-display text-3xl">{{ $stats['avg_rating'] }} / 5</dd></x-card>
            <x-card class="p-5"><dt class="eyebrow text-olive-600">Réclamations</dt><dd class="mt-2 font-display text-3xl">{{ $stats['total_complaints'] }}</dd></x-card>
            <x-card class="p-5"><dt class="eyebrow text-olive-600">En cours</dt><dd class="mt-2 font-display text-3xl">{{ $stats['open_complaints'] }}</dd></x-card>
            <x-card class="p-5"><dt class="eyebrow text-olive-600">Achat vérifié</dt><dd class="mt-2 font-display text-3xl">{{ $stats['verified_purchases'] }}</dd></x-card>
        </div>

        <div class="grid gap-8 lg:grid-cols-2">
            <!-- Mes avis -->
            <x-card>
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div><p class="eyebrow mb-2 text-olive-600">Vos avis</p><h2 class="font-display text-2xl">Vos avis publiés</h2></div>
                </div>
                @forelse($feedbacks as $feedback)
                    <div class="border-t border-stone-100 py-4 first:border-0 first:pt-0">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="flex items-center gap-2 text-sm font-semibold">
                                @for($i=1; $i<=5; $i++)
                                    <span class="{{ $i <= $feedback->rating ? 'text-gold' : 'text-stone-300' }}" aria-hidden="true">★</span>
                                @endfor
                                <span class="ml-1 font-semibold">{{ $feedback->rating }} / 5</span>
                            </p>
                            <span class="text-xs text-stone-500">{{ $feedback->product->name ?? 'Produit #' . $feedback->oil_product_id }} · {{ $feedback->created_at->toDateString() }}</span>
                        </div>
                        @if($feedback->comment)<p class="mt-2 text-sm leading-6">{{ $feedback->comment }}</p>@endif
                        @if($feedback->verified_purchase)
                            <span class="mt-2 inline-flex items-center gap-1 text-xs text-olive-700 bg-olive-50 px-2 py-1 rounded-full">
                                <x-icon name="shield" class="h-3 w-3" /> Achat vérifié
                            </span>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a class="btn-secondary text-sm" href="{{ route('feedback.edit', $feedback) }}">Modifier</a>
                            @if($feedback->hasVerifiedPurchase())
                                <span class="text-xs text-olive-600">Achat vérifié ✓</span>
                            @else
                                <form method="POST" action="{{ route('products.proof.upload', $feedback->oil_product_id) }}" class="inline">
                                    @csrf
                                    <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only" id="proof-{{ $feedback->id }}" onchange="this.form.submit()">
                                    <button type="submit" class="btn-secondary text-sm">Ajouter preuve d'achat</button>
                                </form>
                            @endif
                        </div>
                        @if($feedback->last_edited_at)
                            <p class="mt-2 text-xs text-stone-500">Dernière modification : {{ $feedback->last_edited_at->toDateString() }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Aucun avis pour l'instant. <a class="text-link" href="{{ route('catalog.index') }}">Découvrir des produits</a> pour laisser votre premier avis.</p>
                @endforelse
            </x-card>

            <!-- Mes réclamations -->
            <x-card>
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div><p class="eyebrow mb-2 text-olive-600">Vos réclamations</p><h2 class="font-display text-2xl">Suivi de vos signalements</h2></div>
                    <a class="btn-primary text-sm" href="{{ route('complaints.create') }}">Nouvelle réclamation</a>
                </div>
                @forelse($complaints as $complaint)
                    <div class="border-t border-stone-100 py-4 first:border-0 first:pt-0">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            @php $dot = ['open' => 'bg-gold', 'in_review' => 'bg-olive-500', 'resolved' => 'bg-olive-800', 'rejected' => 'bg-stone-300'][$complaint->status->value] ?? 'bg-stone-300'; @endphp
                            <p class="flex items-center gap-2"><span class="h-2 w-2 rounded-full {{ $dot }}"></span><span class="role-badge">{{ $complaint->status->label() }}</span></p>
                            <span class="text-xs text-stone-500">{{ $complaint->created_at->toDateString() }}</span>
                        </div>
                        <h3 class="font-display mt-2 text-xl">{{ $complaint->subject }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-stone-500">{{ $complaint->description }}</p>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <a class="text-link" href="{{ route('complaints.show', $complaint) }}">Suivre <x-icon name="arrow" class="h-4 w-4" /></a>
                            <a class="text-link" href="{{ route('trace.show', $complaint->product->slug ?? $complaint->oil_product_id) }}">Voir la traçabilité du produit</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Aucune réclamation pour l'instant.</p>
                @endforelse
            </x-card>
        </div>

        <div class="grid gap-8 lg:grid-cols-2 mt-8">
            <!-- Notifications -->
            <x-card>
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div><p class="eyebrow mb-2 text-olive-600">Notifications</p><h2 class="font-display text-2xl">Dernières alertes</h2></div>
                    @if($unread_notifications > 0)
                        <form method="POST" action="{{ route('my-trace.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="btn-secondary text-sm">{{ $unread_notifications }} non lues — Tout marquer comme lu</button>
                        </form>
                    @endif
                </div>
                @forelse($notifications as $notification)
                    <div class="border-t border-stone-100 py-3 first:border-0 first:pt-0 {{ $notification->read ? 'opacity-60' : '' }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-sm">{{ $notification->title }}</p>
                                <p class="mt-1 text-sm text-stone-500">{{ $notification->message }}</p>
                                <p class="mt-1 text-xs text-stone-400">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @unless($notification->read)
                                <form method="POST" action="{{ route('my-trace.notifications.read', $notification) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs text-link">Marquer comme lu</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Aucune notification.</p>
                @endforelse
            </x-card>

            <!-- Achats vérifiés / Produits scannés -->
            <x-card>
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div><p class="eyebrow mb-2 text-olive-600">Achats vérifiés</p><h2 class="font-display text-2xl">Vos preuves d'achat</h2></div>
                </div>
                @forelse($verified_purchases as $purchase)
                    <div class="border-t border-stone-100 py-3 first:border-0 first:pt-0">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $purchase->product->name ?? 'Produit #' . $purchase->oil_product_id }}</p>
                                <p class="text-sm text-stone-500">Vérifié le {{ $purchase->verified_at->toDateString() }} · Type : {{ $purchase->proof_type }}</p>
                            </div>
                            @if($purchase->proof_path)
                                <a href="{{ Storage::url($purchase->proof_path) }}" target="_blank" class="text-link text-sm">Voir la preuve</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Aucun achat vérifié. <a class="text-link" href="{{ route('catalog.index') }}">Achetez un produit</a> puis ajoutez votre preuve d'achat depuis sa page de traçabilité.</p>
                @endforelse
            </x-card>
        </div>
    </div>
@endsection