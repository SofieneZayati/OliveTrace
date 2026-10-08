@extends('layouts.admin')
@section('title', $profile ? 'Edit distributor profile' : 'Create distributor profile')
@section('content')
    <div class="mb-8 max-w-2xl"><p class="eyebrow mb-4 text-olive-600">Your distribution workspace</p><h1 class="display-title text-4xl">{{ $profile ? 'Update your company details.' : 'Set up your distributor profile.' }}</h1><p class="mt-4 text-sm leading-6 text-stone-500">An active profile is required before you can plan shipments.</p></div>
    <x-card class="max-w-3xl">
        <x-validation-errors class="mb-6" />
        <form method="POST" action="{{ $profile ? route('distributor.profile.update') : route('distributor.profile.store') }}" class="space-y-6">
            @csrf
            @if($profile) @method('PATCH') @endif
            <div class="grid gap-6 sm:grid-cols-2">
                <x-production-field name="company_name" label="Company name" :value="$profile?->company_name" maxlength="150" :required="true" />
                <x-production-field name="region" label="Region" :value="$profile?->region" maxlength="100" :required="true" />
                <x-production-field name="phone" label="Phone" type="tel" :value="$profile?->phone" maxlength="30" :required="true" />
                <x-production-field name="address" label="Business address" type="textarea" :value="$profile?->address" maxlength="255" :required="true" />
            </div>
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><x-primary-button>{{ $profile ? 'Save profile' : 'Create profile' }}</x-primary-button><a class="btn-secondary" href="{{ route('distributor.profile.show') }}">Cancel</a></div>
        </form>
    </x-card>
@endsection
