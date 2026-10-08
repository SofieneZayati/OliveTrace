@extends('layouts.admin')
@section('title', 'My mill')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Your huilerie</p>
            <h1 class="display-title text-4xl sm:text-5xl">{{ $mill->name }}</h1>
            <p class="mt-4 text-sm text-stone-500">Everything OliveTrace shows about your mill comes from here.</p>
        </div>
        <x-button-link href="{{ route('mill.edit') }}">Edit my mill</x-button-link>
    </div>
    <x-card class="max-w-2xl">
        <dl class="grid gap-6 sm:grid-cols-2">
            <div><dt class="text-sm text-stone-500">Mill name</dt><dd class="mt-1 font-medium">{{ $mill->name }}</dd></div>
            <div><dt class="text-sm text-stone-500">Region</dt><dd class="mt-1 font-medium">{{ $mill->region }}</dd></div>
            <div><dt class="text-sm text-stone-500">Extraction type</dt><dd class="mt-1 font-medium">{{ ucwords(str_replace('_', ' ', $mill->extraction_type)) }}</dd></div>
            <div><dt class="text-sm text-stone-500">Capacity</dt><dd class="mt-1 font-medium">{{ $mill->capacity ? number_format($mill->capacity).' kg/h' : '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Contact</dt><dd class="mt-1 font-medium">{{ $mill->contact ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Registered</dt><dd class="mt-1 font-medium">{{ $mill->created_at?->format('d M Y') ?? '—' }}</dd></div>
        </dl>
        <div class="mt-8 border-t border-stone-200 pt-6">
            <p class="text-sm text-stone-500">Linked account</p>
            <p class="mt-1 font-medium">{{ auth()->user()->name }} — {{ auth()->user()->email }}</p>
            <p class="mt-3 text-sm text-stone-500">Ask an administrator if this mill needs to be linked to another account.</p>
        </div>
    </x-card>
    <a href="{{ route('dashboard') }}" class="mt-6 inline-block text-sm text-olive-700 underline">Back to dashboard</a>
@endsection
