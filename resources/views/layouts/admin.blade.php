<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#283e29">
    <title>@yield('title', 'Workspace') | OliveTrace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream font-sans antialiased lg:flex">
    <a href="#main-content" class="sr-only z-50 bg-white p-3 focus:not-sr-only focus:absolute">Skip to content</a>
    <aside class="flex flex-col bg-olive-900 px-5 py-6 lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:py-9">
        <a href="{{ route('home') }}" class="px-2" aria-label="OliveTrace home"><x-brand :light="true" /></a>
        <p class="eyebrow mt-10 hidden px-4 text-olive-100/50 lg:block">Your workspace</p>
        <nav aria-label="Main navigation" class="mt-5 flex flex-wrap gap-2 lg:flex-col lg:gap-1">
            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="sidebar-link" @if(request()->routeIs('dashboard', 'admin.dashboard')) aria-current="page" @endif><x-icon name="grid" />Dashboard</a>
            @can('access-admin')
                <a href="{{ route('admin.users.index') }}" class="sidebar-link" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif><x-icon name="users" />Users</a>
            @endcan
            <a href="{{ route('profile.edit') }}" class="sidebar-link" @if(request()->routeIs('profile.*')) aria-current="page" @endif><x-icon name="user" />Profile</a>
            <form method="POST" action="{{ route('logout') }}" class="lg:mt-4">
                @csrf
                <button type="submit" class="sidebar-link w-full"><x-icon name="logout" />Logout</button>
            </form>
        </nav>
        <div class="mt-auto hidden pt-10 lg:block">
            <div class="rounded-xl border border-white/10 p-4">
                <x-icon name="leaf" class="mb-3 h-7 w-7 text-olive-100/70" />
                <p class="font-display text-lg text-cream">Growing together.</p>
                <p class="mt-2 text-xs leading-5 text-olive-100/60">One shared foundation for the OliveTrace community.</p>
            </div>
            <div class="mt-6 flex items-center gap-3 border-t border-white/10 px-2 pt-5">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white/10 font-semibold text-cream">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div class="min-w-0"><p class="truncate text-sm text-cream">{{ auth()->user()->name }}</p><p class="mt-1 text-xs text-olive-100/60">{{ auth()->user()->role->label() }} account</p></div>
            </div>
        </div>
    </aside>
    <div class="min-w-0 flex-1">
        <header class="flex min-h-24 flex-wrap items-center justify-between gap-4 border-b border-olive-800/10 bg-white/60 px-5 py-5 sm:px-10">
            <p class="text-sm text-stone-500">Back Office <span class="mx-2 text-stone-300">/</span> <span class="font-medium text-olive-800">@yield('title', 'Workspace')</span></p>
            <span class="role-badge"><span class="h-1.5 w-1.5 rounded-full bg-olive-500"></span>{{ auth()->user()->role->label() }} workspace</span>
        </header>
        <main id="main-content" class="mx-auto max-w-7xl px-5 py-8 sm:px-10 sm:py-10">
            <x-alerts />
            @yield('content')
        </main>
    </div>
</body>
</html>
