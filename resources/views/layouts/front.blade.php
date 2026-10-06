<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#283e29">
    <meta name="description" content="OliveTrace — a shared workspace for the people behind Tunisian olive oil.">
    <title>@yield('title', 'Home') | OliveTrace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-cream font-sans antialiased">
    @include('layouts.navigation')
    <main id="main-content" class="page-width flex-1 py-8 sm:py-12">
        <x-alerts />
        @yield('content')
    </main>
    <footer class="border-t border-olive-800/10">
        <div class="page-width flex flex-col justify-between gap-5 py-7 sm:flex-row sm:items-center">
            <a href="{{ route('home') }}" aria-label="OliveTrace home"><x-brand class="text-xl" /></a>
            <p class="text-sm text-stone-500">Tunisian olive oil. A story worth connecting.</p>
            <span class="eyebrow text-olive-600">Rooted in Tunisia</span>
        </div>
    </footer>
</body>
</html>
