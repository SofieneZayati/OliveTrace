@extends('layouts.front')
@section('title', 'Produit introuvable')
@section('content')
    <div class="mx-auto max-w-2xl text-center">
        <p class="eyebrow mb-4 text-olive-600">Traçabilité consommateur</p>
        <h1 class="display-title mb-6 text-4xl">Aucun produit trouvé.</h1>
        <p class="mb-8 text-lg leading-8 text-stone-600">Vérifiez le code ou contactez le vendeur.</p>
        <x-card>
            <p class="text-sm leading-7 text-stone-500">Le code <span class="font-semibold text-stone-700">{{ $slug }}</span> ne correspond à aucun produit public sur OliveTrace. Le produit n'a peut-être pas encore été publié, ou la bouteille scannée n'existe pas.</p>
            <ul class="mx-auto mt-4 max-w-md space-y-2 text-left text-sm leading-6 text-stone-500">
                <li>· Vérifiez que le QR code est bien celui de la bouteille OliveTrace</li>
                <li>· Réessayez en saisissant manuellement le code produit ou le numéro de lot</li>
                <li>· Sinon, contactez le vendeur pour obtenir le bon lien de traçabilité</li>
            </ul>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <a class="btn-primary inline-flex items-center gap-2" href="{{ route('trace.search') }}">🔍 Nouveau scan</a>
                <a class="btn-secondary" href="{{ route('home') }}">Retour à l'accueil</a>
            </div>
        </x-card>
    </div>
@endsection
