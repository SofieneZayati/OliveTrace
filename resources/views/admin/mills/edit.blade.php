@extends('layouts.admin')
@section('title', 'Edit mill')
@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h1 class="display-title text-4xl sm:text-5xl">Edit {{ $mill->name }}</h1>
        <span class="role-badge"><x-icon name="drop" class="h-4 w-4" />Mill directory</span>
    </div>
    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.mills.update', $mill) }}" class="space-y-6">
            @csrf
            @method('PATCH')
            <div>
                <x-input-label for="name" value="Mill name" />
                <x-text-input id="name" name="name" value="{{ old('name', $mill->name) }}" required maxlength="255" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="region" value="Region" />
                    <x-text-input id="region" name="region" value="{{ old('region', $mill->region) }}" required maxlength="100" class="mt-1 w-full" />
                    <x-input-error :messages="$errors->get('region')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="extraction_type" value="Extraction type" />
                    <select id="extraction_type" name="extraction_type" required class="mt-1 w-full">
                        <option value="">Choose a method</option>
                        @foreach ($extractionTypes as $type)
                            <option value="{{ $type }}" @selected(old('extraction_type', $mill->extraction_type) === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('extraction_type')" class="mt-2" />
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="capacity" value="Capacity (kg of olives / hour)" />
                    <x-text-input id="capacity" type="number" min="0" name="capacity" value="{{ old('capacity', $mill->capacity) }}" class="mt-1 w-full" />
                    <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="contact" value="Contact" />
                    <x-text-input id="contact" name="contact" value="{{ old('contact', $mill->contact) }}" maxlength="50" class="mt-1 w-full" />
                    <x-input-error :messages="$errors->get('contact')" class="mt-2" />
                </div>
            </div>
            <div class="border-t border-stone-200 pt-6">
                <p class="text-sm text-stone-500">Owner account</p>
                @if ($mill->user)
                    <a href="{{ route('admin.users.show', $mill->user) }}" class="text-link mt-1 inline-block font-medium">{{ $mill->user->name }} — {{ $mill->user->email }}</a>
                    <p class="mt-2 text-sm text-stone-500">The owner is managed from the user account: promoting it to Miller creates this mill, demoting it archives the mill.</p>
                @else
                    <p class="mt-1 font-medium text-stone-500">No linked account.</p>
                @endif
            </div>
            <div class="flex items-center gap-4"><x-primary-button>Save changes</x-primary-button><a href="{{ route('admin.mills.show', $mill) }}" class="text-sm text-stone-600 underline">Cancel</a></div>
        </form>
    </x-card>
@endsection
