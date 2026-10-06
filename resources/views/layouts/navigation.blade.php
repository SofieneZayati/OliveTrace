<a href="#main-content" class="sr-only focus:not-sr-only">Skip to content</a>
<nav aria-label="Main navigation" class="border-b border-stone-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-6">
        <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-olive-800">OliveTrace<span class="ml-1 text-olive-500">.</span></a>
        <div class="flex flex-wrap items-center gap-4 text-sm font-medium">
            @guest
                <a href="{{ route('home') }}" class="hover:text-olive-700">Home</a>
                <a href="{{ route('login') }}" class="hover:text-olive-700">Login</a>
                <x-button-link href="{{ route('register') }}">Register</x-button-link>
            @else
                @if (auth()->user()->role === \App\Enums\Role::Consumer)
                    <a href="{{ route('home') }}" class="hover:text-olive-700">Home</a>
                @endif
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" @if(request()->routeIs('dashboard', 'admin.dashboard')) aria-current="page" @endif class="hover:text-olive-700">Dashboard</a>
                @can('access-admin')
                    <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif class="hover:text-olive-700">Users</a>
                @endcan
                <a href="{{ route('profile.edit') }}" class="hover:text-olive-700">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-stone-300 px-3 py-2 hover:bg-stone-100">Logout</button>
                </form>
            @endguest
        </div>
    </div>
</nav>
