<a href="#main-content" class="sr-only z-50 bg-white p-3 focus:not-sr-only focus:absolute">Skip to content</a>
<nav aria-label="Main navigation" class="border-b border-olive-800/10 bg-cream" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="page-width flex min-h-24 flex-wrap items-center justify-between gap-4 py-5">
        <a href="{{ route('home') }}" aria-label="OliveTrace home"><x-brand /></a>
        <button type="button" class="btn-secondary px-3 sm:hidden" aria-controls="front-navigation" :aria-expanded="open.toString()" @click="open = !open">
            <x-icon name="menu" /><span class="sr-only">Toggle navigation</span>
        </button>
        <div id="front-navigation" class="hidden w-full flex-col gap-5 pb-2 text-sm font-medium sm:flex sm:w-auto sm:flex-row sm:items-center sm:gap-8 sm:pb-0" :class="{ '!flex': open }">
            <a href="{{ route('catalog.index') }}" @if(request()->routeIs('catalog.*')) aria-current="page" @endif class="nav-link">Catalog</a>
            @guest
                <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif class="nav-link">Home</a>
                <a href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif class="nav-link">Login</a>
                <x-button-link href="{{ route('register') }}">Register <x-icon name="arrow" class="h-4 w-4" /></x-button-link>
            @else
                @if (auth()->user()->role === \App\Enums\Role::Consumer)
                    <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif class="nav-link">Home</a>
                @endif
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" @if(request()->routeIs('dashboard', 'admin.dashboard')) aria-current="page" @endif class="nav-link">Dashboard</a>
                @can('access-admin')
                    <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif class="nav-link">Users</a>
                @endcan
                <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif class="nav-link">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary py-2">Logout <x-icon name="logout" class="h-4 w-4" /></button>
                </form>
            @endguest
        </div>
    </div>
</nav>
