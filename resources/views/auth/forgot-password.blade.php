<x-guest-layout>
    <p class="eyebrow mb-3 text-olive-600">Account recovery</p>
    <h1 class="auth-heading">Let's get you back in.</h1>
    <p class="mb-7 mt-3 text-sm leading-6 text-stone-500">Enter your account's email address to request a password reset link.</p>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <x-primary-button class="w-full">{{ __('Email Password Reset Link') }}<x-icon name="arrow" class="h-4 w-4" /></x-primary-button>
    </form>
    <a href="{{ route('login') }}" class="text-link mt-7 text-center">Back to login</a>
</x-guest-layout>
