<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Workspace') | OliveTrace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 font-sans text-stone-900 antialiased">
    @include('layouts.navigation')
    <div class="border-b border-stone-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-4 text-sm text-stone-600 sm:px-6">Back Office <span class="mx-2 text-stone-300">/</span> {{ auth()->user()->role->label() }} workspace</div>
    </div>
    <main id="main-content" class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <x-alerts />
        @yield('content')
    </main>
</body>
</html>
