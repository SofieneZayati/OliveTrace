@props(['name' => 'leaf'])
<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('arrow')<path d="M5 12h14m-6-6 6 6-6 6" />@break
        @case('grid')<rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" />@break
        @case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /><circle cx="9" cy="7" r="4" />@break
        @case('user')<circle cx="12" cy="8" r="4" /><path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2" />@break
        @case('logout')<path d="M9 21H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5m7 14 5-5-5-5m-9 5h14" />@break
        @case('lock')<rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2" />@break
        @case('check')<path d="m5 12 4 4L19 6" />@break
        @case('sun')<circle cx="12" cy="12" r="4" /><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5" />@break
        @case('menu')<path d="M4 6h16M4 12h16M4 18h16" />@break
        @default<path d="M20 3C10 2 3 6 3 13a7 7 0 0 0 7 7c7 0 10-7 10-17Z" /><path d="M4 21 16 9m-8 8v-5m0 5h5" />
    @endswitch
</svg>
