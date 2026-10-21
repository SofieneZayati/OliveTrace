@extends('emails.layout')

@section('content')
    <p class="eyebrow mb-4 text-olive-600">Mise à jour de votre réclamation</p>
    <h1 class="display-title mb-6">{{ $complaint->subject }}</h1>

    <x-card class="mb-6">
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-stone-500">Ancien statut</dt><dd class="mt-1 font-semibold">{{ $oldStatusLabel }}</dd></div>
            <div><dt class="text-stone-500">Nouveau statut</dt><dd class="mt-1 font-semibold text-olive-600">{{ $newStatusLabel }}</dd></div>
            <div><dt class="text-stone-500">Produit</dt><dd class="mt-1 font-semibold">#{{ $complaint->oil_product_id }}</dd></div>
        </dl>
    </x-card>

    @if($complaint->admin_response)
        <x-card class="mb-6">
            <p class="eyebrow mb-2 text-olive-600">Réponse de l'équipe OliveTrace</p>
            <p class="text-sm leading-6">{{ $complaint->admin_response }}</p>
        </x-card>
    @endif

    <div class="text-center">
        <a href="{{ route('complaints.show', $complaint) }}" class="btn-primary">
            Voir ma réclamation
        </a>
    </div>
@endsection