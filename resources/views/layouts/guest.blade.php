@extends('layouts.front')
@section('title', match (true) {
    request()->routeIs('login') => 'Login',
    request()->routeIs('register') => 'Register',
    request()->routeIs('password.request') => 'Forgot password',
    request()->routeIs('password.reset') => 'Reset password',
    request()->routeIs('password.confirm') => 'Confirm password',
    request()->routeIs('verification.notice') => 'Verify email',
    default => 'Account',
})
@section('content')
    <div class="auth-shell mx-auto my-2 max-w-5xl md:grid-cols-[.9fr_1fr] sm:my-6">
        <aside class="auth-art hidden md:block" aria-label="OliveTrace inspiration">
            <img src="{{ asset('images/olive-grove.jpg') }}" alt="" class="hero-image absolute inset-0" width="1122" height="1402">
            <div class="absolute inset-0 bg-gradient-to-t from-olive-900 via-olive-900/20 to-olive-900/10"></div>
            <div class="relative flex h-full min-h-[540px] flex-col justify-end p-10 text-white">
                <x-icon name="leaf" class="mb-7 h-10 w-10 text-olive-100" />
                <p class="eyebrow mb-4 text-white/75">Welcome to OliveTrace</p>
                <p class="display-title text-4xl">Good things start<br>at the roots.</p>
                <p class="mt-5 max-w-xs text-sm leading-6 text-white/75">A shared space for the people behind Tunisian olive oil.</p>
            </div>
        </aside>
        <div class="flex flex-col justify-center px-6 py-9 sm:px-12 sm:py-12">
            {{ $slot }}
        </div>
    </div>
@endsection
