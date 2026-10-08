@extends('layouts.admin')
@section('title', 'User details')
@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h1 class="display-title text-4xl sm:text-5xl">{{ $user->name }}</h1>
        <x-button-link href="{{ route('admin.users.edit', $user) }}">Edit user</x-button-link>
    </div>
    <x-card class="max-w-2xl">
        <dl class="grid gap-6 sm:grid-cols-2">
            <div><dt class="text-sm text-stone-500">Email</dt><dd class="mt-1 break-all font-medium">{{ $user->email }}</dd></div>
            <div><dt class="text-sm text-stone-500">Role</dt><dd class="mt-1 font-medium">{{ $user->role->label() }}</dd></div>
            <div><dt class="text-sm text-stone-500">Account status</dt><dd class="mt-1 font-medium">{{ $user->is_active ? 'Active' : 'Inactive' }}</dd></div>
            <div><dt class="text-sm text-stone-500">Registered</dt><dd class="mt-1 font-medium">{{ $user->created_at->format('d M Y') }}</dd></div>
        </dl>
        @if ($mill && $user->hasRole(\App\Enums\Role::Miller))
            <div class="mt-8 border-t border-stone-200 pt-6">
                <p class="eyebrow mb-4 text-olive-600">Owned mill</p>
                <dl class="grid gap-6 sm:grid-cols-2">
                    <div><dt class="text-sm text-stone-500">Mill name</dt><dd class="mt-1 font-medium">{{ $mill->name }}</dd></div>
                    <div><dt class="text-sm text-stone-500">Region</dt><dd class="mt-1 font-medium">{{ $mill->region ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-stone-500">Extraction</dt><dd class="mt-1 font-medium">{{ $mill->extraction_type ? ucwords(str_replace('_', ' ', $mill->extraction_type)) : '—' }}</dd></div>
                    <div><dt class="text-sm text-stone-500">Capacity</dt><dd class="mt-1 font-medium">{{ $mill->capacity ? number_format($mill->capacity).' kg/h' : '—' }}</dd></div>
                    <div><dt class="text-sm text-stone-500">Contact</dt><dd class="mt-1 font-medium">{{ $mill->contact ?? '—' }}</dd></div>
                </dl>
                <a href="{{ route('admin.mills.index', ['search' => $mill->name]) }}" class="text-link mt-5 inline-flex items-center gap-2">Open in the mill directory <x-icon name="arrow" class="h-4 w-4" /></a>
            </div>
        @endif
        <div class="mt-8 border-t border-stone-200 pt-6">
            @if (auth()->id() !== $user->id)
                <p class="mb-3 text-sm text-stone-600">Deletion is permanent. Deactivate accounts with linked history instead.</p>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" x-data @submit="if (!confirm('Delete this user permanently?')) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Delete user</x-danger-button>
                </form>
            @else
                <p class="text-sm text-stone-500">You cannot delete your own admin account.</p>
            @endif
        </div>
    </x-card>
    <a href="{{ route('admin.users.index') }}" class="mt-6 inline-block text-sm text-olive-700 underline">Back to users</a>
@endsection
