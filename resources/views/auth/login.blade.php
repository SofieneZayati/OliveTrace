<x-guest-layout>
    <p class="eyebrow mb-3 text-olive-600">Your OliveTrace account</p>
    <h1 class="auth-heading">Welcome back.</h1>
    <p class="mb-8 mt-3 text-sm leading-6 text-stone-500">Sign in to your account and make yourself at home.</p>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center gap-2 text-xs text-stone-500">
                <input id="remember_me" type="checkbox" class="focus:ring-olive-500" name="remember">{{ __('Remember me') }}
            </label>
            @if (Route::has('password.request'))
                <a class="text-link text-xs" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
        </div>
        <x-primary-button class="w-full">{{ __('Log in') }}<x-icon name="arrow" class="h-4 w-4" /></x-primary-button>
    </form>
    <p class="mt-7 border-t border-stone-100 pt-6 text-center text-sm text-stone-500">New to OliveTrace? <a href="{{ route('register') }}" class="text-link">Create an account</a></p>
</x-guest-layout>
