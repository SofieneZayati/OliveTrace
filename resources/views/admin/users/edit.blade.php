@extends('layouts.admin')
@section('title', 'Edit user')
@section('content')
    <h1 class="mb-6 text-3xl font-bold">Edit {{ $user->name }}</h1>
    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PATCH')
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="role" value="Role" />
                <select id="role" name="role" required class="mt-1 w-full rounded-md border-stone-300">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $user->role->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>
            <div>
                <input type="hidden" name="is_active" value="0">
                <label for="is_active" class="flex items-center gap-3 text-sm"><input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-stone-300 text-olive-700">Active account</label>
                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                @if (auth()->id() === $user->id)<p class="mt-2 text-sm text-stone-500">Your own admin role and active status must be kept.</p>@endif
            </div>
            <div class="flex items-center gap-4"><x-primary-button>Save changes</x-primary-button><a href="{{ route('admin.users.show', $user) }}" class="text-sm text-stone-600 underline">Cancel</a></div>
        </form>
    </x-card>
@endsection
