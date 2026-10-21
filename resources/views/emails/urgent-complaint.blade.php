@extends('emails.layout')

@section('content')
    <p class="eyebrow mb-4 text-olive-600">⚠️ Réclamation urgente</p>
    <h1 class="display-title mb-6">Action requise</h1>

    <x-card class="mb-6">
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="text-stone-500">Produit</dt><dd class="mt-1 font-semibold">#{{ $complaint->oil_product_id }}</dd></div>
            <div><dt class="text-stone-500">Consommateur</dt><dd class="mt-1 font-semibold">{{ $complaint->consumer?->name }} ({{ $complaint->consumer?->email }})</dd></div>
            <div><dt class="text-stone-500">Sujet</dt><dd class="mt-1 font-semibold">{{ $complaint->subject }}</dd></div>
            <div><dt class="text-stone-500">Priorité IA</dt><dd class="mt-1 font-semibold text-red-600">{{ $complaint->ai_priority?->label() }}</dd></div>
            <div><dt class="text-stone-500">Catégorie IA</dt><dd class="mt-1 font-semibold">{{ $complaint->ai_category?->label() }}</dd></div>
            <div><dt class="text-stone-500">Sentiment IA</dt><dd class="mt-1 font-semibold">{{ $complaint->ai_sentiment?->label() }}</dd></div>
        </dl>
    </x-card>

    <x-card class="mb-6">
        <p class="eyebrow mb-2 text-olive-600">Description</p>
        <p class="text-sm leading-6">{{ $complaint->description }}</p>
    </x-card>

    <div class="text-center">
        <a href="{{ route('admin.consumer.complaints.show', $complaint) }}" class="btn-primary">
            Voir et traiter la réclamation
        </a>
    </div>
@endsection