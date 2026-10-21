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
            <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif class="nav-link">Profile</a>
                
                @auth
                    @if(auth()->user()->role === \App\Enums\Role::Consumer)
                        <div class="relative" x-data="{ notifOpen: false, unreadCount: @js(\App\Models\Consumer\ConsumerNotification::unreadCount(auth()->id())) }" @keydown.escape.window="notifOpen = false" @click.outside="notifOpen = false">
                            <button type="button" class="btn-secondary p-2 relative" aria-label="Notifications" @click="notifOpen = !notifOpen" aria-expanded="false" aria-controls="notifications-dropdown">
                                <x-icon name="bell" class="h-5 w-5" />
                                <span x-show="unreadCount > 0" class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center" x-text="unreadCount"></span>
                            </button>
                            <div id="notifications-dropdown" x-show="notifOpen" x-transition:enter="transition ease-out duration-100" x-transition:leave="transition ease-in duration-75" class="absolute right-0 mt-2 w-80 bg-white border border-stone-200 rounded-xl shadow-lg py-2 z-50" x-cloak>
                                <div class="px-4 py-3 border-b border-stone-100 flex items-center justify-between">
                                    <h3 class="font-semibold text-sm">Notifications</h3>
                                    <form method="POST" action="{{ route('my-trace.notifications.read-all') }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-olive-600 hover:underline">Tout marquer comme lu</button>
                                    </form>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    @php $notifications = \App\Models\Consumer\ConsumerNotification::where('consumer_user_id', auth()->id())->latest()->take(10)->get(); @endphp
                                    @forelse($notifications as $notification)
                                        <div class="px-4 py-3 border-b border-stone-100 hover:bg-stone-50 {{ $notification->read ? 'opacity-60' : 'bg-olive-50/50' }}">
                                            <p class="font-semibold text-sm">{{ $notification->title }}</p>
                                            <p class="mt-1 text-sm text-stone-500 line-clamp-2">{{ $notification->message }}</p>
                                            <p class="mt-1 text-xs text-stone-400">{{ $notification->created_at->diffForHumans() }}</p>
                                            @unless($notification->read)
                                                <form method="POST" action="{{ route('my-trace.notifications.read', $notification) }}" class="inline mt-2">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="text-xs text-olive-600 hover:underline">Marquer comme lu</button>
                                                </form>
                                            @endunless
                                        </div>
                                    @empty
                                        <p class="px-4 py-4 text-sm text-stone-500 text-center">Aucune notification.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endauth
                @endauth
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary py-2">Logout <x-icon name="logout" class="h-4 w-4" /></button>
                </form>
            @endguest
        </div>
    </div>
</nav>
