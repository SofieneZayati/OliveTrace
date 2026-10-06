<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') | OliveTrace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 font-sans text-stone-900 antialiased">
    @include('layouts.navigation')
    <main id="main-content" class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <x-alerts />
        @yield('content')
    </main>
    <footer class="mx-auto max-w-6xl border-t border-stone-200 px-4 py-6 text-sm text-stone-500 sm:px-6">OliveTrace &middot; Tunisian olive oil, connected.</footer>
</body>
</html>
