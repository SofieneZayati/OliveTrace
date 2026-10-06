<x-guest-layout>
    <p class="eyebrow mb-3 text-olive-600">Join the community</p>
    <h1 class="auth-heading">Your story starts here.</h1>
    <p class="mb-7 mt-3 text-sm leading-6 text-stone-500">Create your OliveTrace account in a few simple steps.</p>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="mt-2 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" maxlength="255" placeholder="Your full name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" maxlength="255" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <x-primary-button class="mt-2 w-full">{{ __('Register') }}<x-icon name="arrow" class="h-4 w-4" /></x-primary-button>
    </form>
    <p class="mt-6 border-t border-stone-100 pt-5 text-center text-sm text-stone-500">Already registered? <a href="{{ route('login') }}" class="text-link">Login</a></p>
</x-guest-layout>
