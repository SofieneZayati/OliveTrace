@extends(auth()->user()->role === \App\Enums\Role::Consumer ? 'layouts.front' : 'layouts.admin')
@section('title', 'Dashboard')
@section('content')
    <div class="mb-8">
        <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-olive-700">OliveTrace</p>
        <h1 class="text-3xl font-bold">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-3 text-stone-600">Role: {{ auth()->user()->role->label() }}</p>
    </div>
    <x-card>
        <h2 class="text-xl font-semibold">Your workspace</h2>
        <p class="mt-3 text-stone-600">The business modules will appear here.</p>
        @can('access-admin')
            <div class="mt-6"><x-button-link href="{{ route('admin.users.index') }}">Manage users</x-button-link></div>
        @endcan
    </x-card>
@endsection
