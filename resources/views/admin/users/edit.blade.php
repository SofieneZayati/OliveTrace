@extends('layouts.admin')
@section('title', 'Edit user')
@section('content')
    <h1 class="mb-6 display-title text-4xl sm:text-5xl">Edit {{ $user->name }}</h1>
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
            <div x-data="{ miller: {{ in_array(old('role', $user->role->value), ['miller'], true) ? 'true' : 'false' }} }">
                <div>
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" required class="mt-2 w-full" @change="miller = $event.target.value === 'miller'">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $user->role->value) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    <p class="mt-2 text-sm text-stone-500">Choosing <strong>Miller</strong> creates the mill owned by this account. Choosing another role archives its mill.</p>
                </div>

                <div x-show="miller" x-cloak class="mt-6 space-y-5 border-t border-stone-200 pt-6">
                    <div>
                        <p class="eyebrow text-olive-600">Mill details</p>
                        <p class="mt-2 text-sm text-stone-500">{{ $mill ? 'This account already owns a mill, the details below are prefilled.' : 'A new mill row is created for this account once you save.' }}</p>
                    </div>
                    <div>
                        <x-input-label for="mill_name" value="Mill name" />
                        <x-text-input id="mill_name" name="mill[name]" value="{{ old('mill.name', $mill?->name) }}" required maxlength="255" class="mt-1 w-full" placeholder="Huilerie Zitouna" />
                        <x-input-error :messages="$errors->get('mill.name')" class="mt-2" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="mill_region" value="Region" />
                            <x-text-input id="mill_region" name="mill[region]" value="{{ old('mill.region', $mill?->region) }}" required maxlength="100" class="mt-1 w-full" placeholder="Sfax" />
                            <x-input-error :messages="$errors->get('mill.region')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="mill_extraction_type" value="Extraction type" />
                            <select id="mill_extraction_type" name="mill[extraction_type]" required class="mt-1 w-full">
                                <option value="">Choose a method</option>
                                @foreach (\App\Models\Mill::EXTRACTION_TYPES as $type)
                                    <option value="{{ $type }}" @selected(old('mill.extraction_type', $mill?->extraction_type) === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('mill.extraction_type')" class="mt-2" />
                        </div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="mill_capacity" value="Capacity (kg of olives / hour)" />
                            <x-text-input id="mill_capacity" type="number" min="0" name="mill[capacity]" value="{{ old('mill.capacity', $mill?->capacity) }}" class="mt-1 w-full" placeholder="2000" />
                            <x-input-error :messages="$errors->get('mill.capacity')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="mill_contact" value="Contact" />
                            <x-text-input id="mill_contact" name="mill[contact]" value="{{ old('mill.contact', $mill?->contact) }}" maxlength="50" class="mt-1 w-full" placeholder="+216 74 000 000" />
                            <x-input-error :messages="$errors->get('mill.contact')" class="mt-2" />
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('mill')" class="mt-2" />
                </div>
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
