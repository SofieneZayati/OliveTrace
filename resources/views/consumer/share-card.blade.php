@extends('layouts.front')
@section('title', 'Partager : {{ $share['product_name'] }}')
@section('content')
    <div class="mx-auto max-w-xl">
        <p class="eyebrow mb-4 text-olive-600">Partager cette huile</h1>
        <h1 class="display-title mb-8 text-4xl">Invitez à découvrir {{ $share['product_name'] }}</h1>

        <x-card class="mb-8 p-8 text-center">
            @if($share['image_url'])
                <img src="{{ $share['image_url'] }}" alt="{{ $share['product_name'] }}" class="mx-auto mb-6 max-h-64 rounded-xl">
            @else
                <div class="mx-auto mb-6 grid h-48 w-48 place-items-center rounded-xl bg-olive-50">
                    <x-icon name="leaf" class="h-16 w-16 text-olive-600" />
                </div>
            @endif

            <h2 class="font-display text-3xl mb-2">{{ $share['product_name'] }}</h2>
            <p class="text-stone-500 mb-2">{{ $share['brand'] }}</p>
            <p class="text-sm text-stone-500 mb-6">{{ $share['bottle_volume'] }} ml · {{ $share['review_count'] }} avis</p>

            <x-rating-stars :value="$share['avg_rating']" :count="$share['review_count']" />

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ $share['trace_url'] }}" target="_blank" class="btn-primary">
                    Voir la traçabilité complète
                </a>
                <button onclick="navigator.share({ title: '{{ $share['product_name'] }}', text: 'Découvrez cette huile d\\'olive sur OliveTrace', url: '{{ $share['trace_url'] }}' }).catch(() => {})" class="btn-secondary">
                    Partager
                </button>
            </div>

            @if($share['qr_code_url'])
                <div class="mt-8">
                    <p class="text-sm text-stone-500 mb-3">QR Code pour impression</p>
                    <img src="{{ $share['qr_code_url'] }}" alt="QR Code traçabilité" class="mx-auto w-40 h-40">
                </div>
            @endif
        </x-card>

        <div class="text-center text-sm text-stone-500">
            <p>Scannez le QR code ou partagez le lien pour faire découvrir l'histoire de cette huile.</p>
        </div>
    </div>
@endsection