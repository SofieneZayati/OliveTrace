@extends(auth()->user()->role === \App\Enums\Role::Consumer ? 'layouts.front' : 'layouts.admin')
@section('title', 'Dashboard')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="eyebrow mb-4 text-olive-600">OliveTrace / Your dashboard</p>
            <h1 class="display-title text-4xl sm:text-5xl">Welcome, {{ auth()->user()->name }}.</h1>
            <p class="mt-4 text-sm text-stone-500">A shared space. A fresh beginning.</p>
        </div>
        <span class="role-badge">Role: {{ auth()->user()->role->label() }}</span>
    </div>
    <section class="relative mb-7 overflow-hidden rounded-2xl bg-olive-800 p-7 text-cream sm:p-10"
        aria-labelledby="workspace-heading">
        <x-icon name="leaf" class="pointer-events-none absolute -right-6 -top-8 h-64 w-64 rotate-12 text-cream/5" />
        <div class="relative max-w-xl">
            <p class="eyebrow mb-4 text-olive-100/70">Growing together</p>
            @if(auth()->user()->role === \App\Enums\Role::Producer)
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Your origin starts here.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Build your producer profile, manage your farms, record
                    harvests, and request certifications.</p>
                <div class="flex flex-wrap gap-4 mt-6">
                    <a href="{{ route('producer.farms.index') }}" class="btn-secondary">Explore my farms <x-icon
                            name="arrow" /></a>
                    <a href="{{ route('certification.producer.requests.index') }}" class="btn-secondary">My Certificate Requests
                        <x-icon name="arrow" /></a>
                </div>
            @elseif(auth()->user()->role === \App\Enums\Role::Laboratory)
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Verify the origin.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Process certification requests, analyze lab results, and
                    issue official certificates to producers.</p>
                <a href="{{ route('lab.requests.index') }}" class="btn-secondary mt-6">View Certification Requests <x-icon
                        name="arrow" /></a>
            @elseif(auth()->user()->isAdmin())
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Look after the origin story.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Inspect producer profiles, farms and certificates, correct
                    details and keep invalid origin information out of public view.</p>
                <a href="{{ route('admin.farms.index') }}" class="btn-secondary mt-6">Explore community farms <x-icon
                        name="arrow" /></a>
            @elseif(auth()->user()->role === \App\Enums\Role::Miller)
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Your mill is part of the story.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">Keep your huilerie details accurate: region, extraction
                    method and hourly capacity.</p>
                <a href="{{ route('mill.show') }}" class="btn-secondary mt-6">Manage my mill <x-icon name="arrow" /></a>
            @else
                <h2 id="workspace-heading" class="font-display text-3xl sm:text-4xl">Your workspace is taking root.</h2>
                <p class="mt-4 text-sm leading-7 text-olive-100/80">The business modules will appear here.</p>
                <p class="mt-1 text-sm leading-7 text-olive-100/80">For now, your account and shared tools are ready.</p>
            @endif
        </div>
    </section>
    <div class="grid gap-5 md:grid-cols-2">
        <x-card>
            <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                    name="user" /></div>
            <h2 class="text-lg font-semibold">Make yourself at home</h2>
            <p class="mb-6 mt-2 text-sm leading-6 text-stone-500">Keep your personal details up to date and manage your
                account security.</p>
            <a href="{{ route('profile.edit') }}" class="text-link inline-flex items-center gap-2">Manage profile <x-icon
                    name="arrow" class="h-4 w-4" /></a>
        </x-card>
        @can('access-admin')
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                        name="users" /></div>
                <h2 class="text-lg font-semibold">Look after the community</h2>
                <p class="mb-6 mt-2 text-sm leading-6 text-stone-500">Manage shared accounts, assign roles, and control who can
                    access OliveTrace.</p>
                <a href="{{ route('admin.users.index') }}" class="text-link inline-flex items-center gap-2">Manage users <x-icon
                        name="arrow" class="h-4 w-4" /></a>
            </x-card>
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                        name="drop" /></div>
                <h2 class="text-lg font-semibold">Keep the mill directory accurate</h2>
                <p class="mb-6 mt-2 text-sm leading-6 text-stone-500">Review every huilerie on OliveTrace, its region,
                    extraction method and hourly capacity.</p>
                <a href="{{ route('admin.mills.index') }}" class="text-link inline-flex items-center gap-2">Browse mills <x-icon
                        name="arrow" class="h-4 w-4" /></a>
            </x-card>
        @elseif(auth()->user()->role === \App\Enums\Role::Miller)
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                        name="drop" /></div>
                @if (auth()->user()->mill)
                    <h2 class="text-lg font-semibold">{{ auth()->user()->mill->name }}</h2>
                    <p class="mb-6 mt-2 text-sm leading-6 text-stone-500">{{ auth()->user()->mill->region }} ·
                        {{ ucwords(str_replace('_', ' ', auth()->user()->mill->extraction_type)) }} ·
                        {{ auth()->user()->mill->capacity ? number_format(auth()->user()->mill->capacity) . ' kg/h' : 'capacity not set' }}.
                        Keep it accurate so the OliveTrace story stays true.</p>
                    <a href="{{ route('mill.show') }}" class="text-link inline-flex items-center gap-2">Manage my mill <x-icon
                            name="arrow" class="h-4 w-4" /></a>
                @else
                    <h2 class="text-lg font-semibold">Your mill is not linked yet</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-500">No huilerie is attached to this account. Ask an administrator
                        to create it.</p>
                @endif
            </x-card>
        @else
            <x-card>
                <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-olive-50 text-olive-700"><x-icon
                        name="check" /></div>
                <h2 class="text-lg font-semibold">Your place in OliveTrace</h2>
                <p class="mt-2 text-sm leading-6 text-stone-500">You're signed in as a
                    {{ strtolower(auth()->user()->role->label()) }}. Your role helps us bring the right workspace to you.</p>
                <p class="mt-6 inline-flex items-center gap-2 text-xs font-semibold text-olive-700"><span
                        class="h-1.5 w-1.5 rounded-full bg-olive-500"></span>Account active</p>
            </x-card>
        @endcan
    </div>
@endsection