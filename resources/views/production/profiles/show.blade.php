@extends('layouts.admin')
@section('title', 'Producer profile')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p class="eyebrow mb-4 text-olive-600">Producer & farm management</p><h1 class="display-title text-4xl">{{ $profile->displayName }}</h1><p class="mt-3 text-stone-500">{{ $profile->companyName ?? 'Independent olive grower' }}</p></div>
        <a class="btn-primary" href="{{ $admin ? route('admin.producers.edit', $profile->id) : route('producer.profile.edit') }}">Edit producer profile <x-icon name="arrow" /></a>
    </div>
    <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <x-card>
            @if($profile->logoPath)<img src="{{ route('producer.logo', $profile->id) }}" alt="{{ $profile->displayName }} logo" class="mb-6 h-28 w-28 rounded-2xl object-cover">@endif
            <span class="role-badge">{{ $profile->isActive ? 'Enabled' : 'Disabled by admin' }}</span>
            <dl class="mt-6 space-y-5 text-sm">
                @foreach(['Phone' => $profile->phone, 'Business address' => $profile->address, 'Public display name' => $profile->isPublic ? 'Allowed on published farm cards' : 'Private'] as $label => $value)
                    <div><dt class="text-stone-500">{{ $label }}</dt><dd class="mt-1 font-medium">{{ $value ?? 'Not provided' }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-6 whitespace-pre-line text-sm leading-7 text-stone-600">{{ $profile->description ?? 'Add a description to introduce your business.' }}</p>
        </x-card>
        <x-card>
            <div class="mb-6 flex items-center justify-between gap-3"><h2 class="font-display text-2xl">Farms & parcels</h2>@unless($admin)<a href="{{ route('producer.farms.create') }}" class="text-link">Add a farm</a>@endunless</div>
            @forelse($profile->farms as $farm)
                <a href="{{ route($admin ? 'admin.farms.show' : 'producer.farms.show', $farm->id) }}" class="mb-3 flex items-center justify-between gap-3 rounded-xl border border-stone-200 p-5 hover:bg-olive-50">
                    <div><p class="font-semibold">{{ $farm->name }}</p><p class="mt-2 text-xs text-stone-500">{{ $farm->governorate }} · {{ $farm->areaHa }} ha · {{ $farm->oliveVariety }}</p></div><span class="text-xs text-stone-500">{{ $farm->status->label() }}</span>
                </a>
            @empty<p class="py-10 text-sm text-stone-500">No farms yet. Add your first parcel to start the origin story.</p>@endforelse
        </x-card>
    </div>
    @unless($admin)
        <x-card class="mt-8 max-w-3xl">
            <h2 class="font-display text-xl">Remove an unused producer profile</h2>
            <p class="my-4 text-sm leading-6 text-stone-500">Deletion is available only when this profile has no farms or linked history. Your login account stays in place.</p>
            <form method="POST" action="{{ route('producer.profile.destroy') }}" x-data @submit="if (!confirm('Delete this producer profile?')) $event.preventDefault()">@csrf @method('DELETE')<button class="btn-danger" type="submit">Delete producer profile</button></form>
        </x-card>
    @endunless
@endsection
