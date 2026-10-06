@extends('layouts.front')
@section('title', 'Home')
@section('content')
    <section class="grid items-center gap-10 pb-12 lg:grid-cols-[1.05fr_1fr] lg:gap-14 lg:pb-16" aria-labelledby="home-heading">
        <div class="py-4 lg:py-10">
            <div class="mb-7 flex items-center gap-3">
                <span class="h-px w-8 bg-gold"></span>
                <p class="eyebrow text-olive-600">From Tunisia, with purpose</p>
            </div>
            <h1 id="home-heading" class="display-title max-w-xl text-[clamp(3.1rem,5.3vw,5.1rem)]">Rooted in tradition.<br><span class="italic text-olive-600">Connected</span><br>by trust.</h1>
            <p class="mt-7 max-w-md text-base leading-7 text-stone-600 sm:text-lg sm:leading-8">Behind every drop of olive oil, there are people, places, and a story. OliveTrace brings them together in one shared workspace.</p>
            <div class="mt-9 flex flex-wrap items-center gap-5">
                @auth
                    <x-button-link href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}">Open your dashboard <x-icon name="arrow" /></x-button-link>
                @else
                    <x-button-link href="{{ route('register') }}">Create an account <x-icon name="arrow" /></x-button-link>
                    <a href="{{ route('login') }}" class="text-link flex items-center gap-2 py-3">Login <span aria-hidden="true">↗</span></a>
                @endauth
            </div>
            <div class="mt-12 flex items-center gap-3 text-xs text-stone-500">
                <span class="flex h-8 w-8 items-center justify-center rounded-full border border-olive-800/15"><x-icon name="leaf" class="h-4 w-4 text-olive-600" /></span>
                <span>Tunisian roots. A shared future.</span>
            </div>
        </div>
        <figure class="relative h-[420px] overflow-hidden rounded-t-[5rem] rounded-b-2xl bg-olive-100 sm:h-[530px] lg:h-[600px] lg:rounded-t-[7rem]">
            <img src="{{ asset('images/olive-grove.jpg') }}" width="1122" height="1402" alt="Sunlight over an olive grove with an old olive tree and a winding path." class="hero-image" fetchpriority="high">
            <div class="absolute inset-0 bg-gradient-to-t from-olive-900/75 via-transparent to-transparent"></div>
            <div class="absolute right-6 top-6 flex h-24 w-24 flex-col items-center justify-center rounded-full border border-white/50 bg-cream/95 text-olive-800 shadow-sm sm:right-8 sm:top-8">
                <x-icon name="leaf" class="mb-2 h-6 w-6" />
                <span class="text-[9px] font-semibold uppercase tracking-[.15em]">Rooted in</span>
                <span class="font-display text-lg">Tunisia</span>
            </div>
            <figcaption class="absolute inset-x-7 bottom-7 text-white sm:inset-x-10 sm:bottom-10">
                <p class="eyebrow mb-3 text-white/80">Our inspiration</p>
                <p class="font-display text-3xl leading-tight sm:text-4xl">Every drop has a story.</p>
                <div class="mt-5 flex items-center gap-3"><span class="h-px w-10 bg-white/50"></span><span class="text-xs text-white/85">The land. The people. The care.</span></div>
            </figcaption>
        </figure>
    </section>
    <section class="border-t border-olive-800/15 py-10 sm:py-12" aria-labelledby="vision-heading">
        <div class="grid gap-10 lg:grid-cols-[.85fr_2fr] lg:gap-14">
            <div>
                <p class="eyebrow mb-4 text-olive-600">Our shared vision</p>
                <h2 id="vision-heading" class="display-title text-3xl sm:text-4xl">One journey.<br>Many hands.</h2>
            </div>
            <div class="grid gap-8 sm:grid-cols-3">
                <article>
                    <x-icon name="users" class="mb-4 h-7 w-7 text-olive-600" />
                    <h3 class="mb-2 text-base font-semibold">People, connected</h3>
                    <p class="text-sm leading-6 text-stone-500">A common space for everyone who shapes the olive oil journey.</p>
                </article>
                <article>
                    <x-icon name="leaf" class="mb-4 h-7 w-7 text-olive-600" />
                    <h3 class="mb-2 text-base font-semibold">Care at every step</h3>
                    <p class="text-sm leading-6 text-stone-500">A shared commitment to the craft, from the grove to everyday life.</p>
                </article>
                <article>
                    <x-icon name="sun" class="mb-4 h-7 w-7 text-olive-600" />
                    <h3 class="mb-2 text-base font-semibold">Proudly Tunisian</h3>
                    <p class="text-sm leading-6 text-stone-500">Inspired by our land and the generations who have cared for it.</p>
                </article>
            </div>
        </div>
    </section>
@endsection
