@extends('layouts.admin')
@section('title', $profile ? 'Edit producer profile' : 'Create producer profile')
@section('content')
    <div class="mb-8 max-w-2xl">
        <p class="eyebrow mb-4 text-olive-600">The people behind the origin</p>
        <h1 class="display-title text-4xl">{{ $profile ? 'Your producer identity.' : 'Start with your story.' }}</h1>
        <p class="mt-4 text-sm leading-6 text-stone-500">Set up the business profile that connects your farms to the traceability chain. Fields marked * are required.</p>
    </div>
    <x-card class="max-w-3xl">
        <form method="POST" enctype="multipart/form-data" action="{{ $admin ? route('admin.producers.update', $profile->id) : route($profile ? 'producer.profile.update' : 'producer.profile.store') }}" class="space-y-6">
            @csrf
            @if($profile) @method('PATCH') @endif
            <div class="grid gap-6 sm:grid-cols-2">
                <x-production-field name="display_name" label="Producer display name" :value="$profile?->display_name" maxlength="120" :required="true" />
                <x-production-field name="company_name" label="Company name" :value="$profile?->company_name" maxlength="150" />
                <x-production-field name="phone" label="Contact phone" type="tel" :value="$profile?->phone" maxlength="30" help="Private contact information; never shown on the public origin card." />
                <x-production-field name="address" label="Business address" :value="$profile?->address" maxlength="255" />
            </div>
            <x-production-field name="description" label="About the producer" type="textarea" :value="$profile?->description" maxlength="3000" />
            <div>
                <label for="logo" class="form-label">Logo or photo</label>
                <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm" aria-describedby="logo-help">
                <p id="logo-help" class="mt-2 text-xs text-stone-500">JPEG, PNG or WebP, up to 2 MB and 3000 × 3000 pixels. Kept private to the owner and admin.</p>
                <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                @if($profile?->logo_path)
                    <img src="{{ route('producer.logo', $profile->id) }}" alt="Current producer logo" class="my-4 h-24 w-24 rounded-xl object-cover">
                    <x-production-toggle name="remove_logo" label="Remove current logo" />
                @endif
            </div>
            <div class="space-y-4 rounded-xl bg-olive-50 p-5">
                <x-production-toggle name="is_public" :checked="$profile?->is_public ?? false" label="Allow my producer display name on the public origin card of farms I choose to publish." />
                @if($admin)<x-production-toggle name="is_active" :checked="$profile->is_active" label="Producer profile enabled for new farms and public origin cards" />@endif
            </div>
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6">
                <x-primary-button>{{ $profile ? 'Save producer profile' : 'Create producer profile' }}</x-primary-button>
                <a class="btn-secondary" href="{{ $admin ? route('admin.producers.show', $profile->id) : route('producer.profile.show') }}">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
