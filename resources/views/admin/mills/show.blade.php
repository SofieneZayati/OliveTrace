@extends('layouts.admin')
@section('title', 'Mill details')
@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Mill directory</p>
            <h1 class="display-title text-4xl sm:text-5xl">{{ $mill->name }}</h1>
        </div>
        <x-button-link href="{{ route('admin.mills.edit', $mill) }}">Edit mill</x-button-link>
    </div>
    <x-card class="max-w-2xl">
        <dl class="grid gap-6 sm:grid-cols-2">
            <div><dt class="text-sm text-stone-500">Mill name</dt><dd class="mt-1 font-medium">{{ $mill->name }}</dd></div>
            <div><dt class="text-sm text-stone-500">Region</dt><dd class="mt-1 font-medium">{{ $mill->region }}</dd></div>
            <div><dt class="text-sm text-stone-500">Extraction type</dt><dd class="mt-1 font-medium">{{ ucwords(str_replace('_', ' ', $mill->extraction_type)) }}</dd></div>
            <div><dt class="text-sm text-stone-500">Capacity</dt><dd class="mt-1 font-medium">{{ $mill->capacity ? number_format($mill->capacity).' kg/h' : '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Contact</dt><dd class="mt-1 font-medium">{{ $mill->contact ?? '—' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Created</dt><dd class="mt-1 font-medium">{{ $mill->created_at?->format('d M Y') ?? '—' }}</dd></div>
        </dl>
        <div class="mt-8 border-t border-stone-200 pt-6">
            <p class="text-sm text-stone-500">Owner account</p>
            @if ($mill->user)
                <a href="{{ route('admin.users.show', $mill->user) }}" class="text-link mt-1 inline-block font-medium">{{ $mill->user->name }} — {{ $mill->user->email }}</a>
            @else
                <p class="mt-1 font-medium text-stone-500">No linked account.</p>
            @endif
        </div>
        <div class="mt-8 border-t border-stone-200 pt-6">
            <p class="mb-3 text-sm text-stone-600">Deleting archives the mill and sends its owner account back to the consumer role.</p>
            <form method="POST" action="{{ route('admin.mills.destroy', $mill) }}" x-data @submit="if (!confirm('Delete this mill? Its owner account goes back to consumer.')) $event.preventDefault()">
                @csrf
                @method('DELETE')
                <x-danger-button>Delete mill</x-danger-button>
            </form>
        </div>
    </x-card>
    <a href="{{ route('admin.mills.index') }}" class="mt-6 inline-block text-sm text-olive-700 underline">Back to mills</a>
@endsection
