@extends('layouts.front')
@section('title', 'Home')
@section('content')
    <div class="py-12 sm:py-20">
        <p class="mb-5 text-sm font-semibold uppercase tracking-widest text-olive-700">Rooted in Tunisia</p>
        <h1 class="max-w-3xl text-4xl font-bold leading-tight sm:text-5xl">A shared workspace for the people behind Tunisian olive oil.</h1>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-stone-600">OliveTrace brings the olive oil journey together, from farm to consumer. Sign in to access your account and dashboard.</p>
        <div class="mt-8 flex flex-wrap gap-4">
            @auth
                <x-button-link href="{{ route('dashboard') }}">Open dashboard</x-button-link>
            @else
                <x-button-link href="{{ route('register') }}">Create an account</x-button-link>
                <a href="{{ route('login') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-stone-100">Login</a>
            @endauth
        </div>
    </div>
@endsection
