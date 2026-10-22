@extends('layouts.front')
@section('title', 'Partager : '.$share['product_name'])
@section('content')
    <div class="mx-auto max-w-xl" x-data="{ message: '' }">
        <p class="eyebrow mb-4 text-olive-600">Partager cette huile</p>
        <h1 class="display-title mb-8 text-4xl">Découvrez cette origine.</h1>
        <x-card class="p-8 text-center">
            @if($share['image_url'])
                <img src="{{ $share['image_url'] }}" alt="{{ $share['product_name'] }}" class="mx-auto mb-6 max-h-64 rounded-xl">
            @endif
            <h2 class="font-display mb-2 text-3xl" x-ref="title">{{ $share['product_name'] }}</h2>
            <p class="mb-2 text-stone-500">{{ $share['brand'] }}</p>
            <p class="mb-6 text-sm text-stone-500">{{ $share['bottle_volume'] }} ml · {{ $share['review_count'] }} avis</p>
            <p class="mb-5 text-sm text-stone-500">{{ $share['verified'] ? 'Certificat valide disponible' : 'Aucun certificat valide confirmé' }}</p>
            <x-rating-stars :value="$share['avg_rating']" :count="$share['review_count']" />
            <div class="my-6 flex justify-center" aria-label="QR Code traçabilité">{!! $share['qr_svg'] !!}</div>
            <label for="share-url" class="mb-2 block text-sm">Lien de traçabilité</label>
            <input id="share-url" x-ref="traceLink" readonly value="{{ $share['trace_url'] }}" class="w-full rounded-lg border-stone-300 text-sm">
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ $share['trace_url'] }}" class="btn-primary">Voir la traçabilité</a>
                <button type="button" class="btn-secondary" x-show="typeof navigator.share === 'function'" @click="navigator.share({title: $refs.title.textContent, url: $refs.traceLink.value}).catch(() => {})">Partager</button>
            </div>
        </x-card>
    </div>
@endsection
