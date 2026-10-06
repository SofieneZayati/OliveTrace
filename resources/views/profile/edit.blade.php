<x-app-layout>
    <x-slot name="header">
        <p class="eyebrow mb-4 text-olive-600">Your account</p>
        <h1 class="display-title text-4xl sm:text-5xl">{{ __('Profile') }}</h1>
        <p class="mt-4 text-sm text-stone-500">Your details, your security, your preferences.</p>
    </x-slot>
    <div class="grid items-start gap-6 xl:grid-cols-2">
        <x-card>@include('profile.partials.update-profile-information-form')</x-card>
        <x-card>@include('profile.partials.update-password-form')</x-card>
        @unless ($user->isAdmin())
            <x-card class="xl:col-span-2">@include('profile.partials.delete-user-form')</x-card>
        @endunless
    </div>
</x-app-layout>
