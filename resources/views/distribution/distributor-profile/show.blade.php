@extends('layouts.admin')
@section('title', 'Distributor profile')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">{{ $profile->region }} / Distributor</p><h1 class="display-title text-4xl sm:text-5xl">{{ $profile->company_name }}</h1><p class="mt-4 text-sm text-stone-500">Your company profile for shipment tracking.</p></div><a class="btn-primary" href="{{ route('distributor.profile.edit') }}">Edit profile <x-icon name="arrow" /></a></div>
    <x-card class="max-w-3xl">
        <span class="role-badge">{{ $profile->is_active ? 'Active' : 'Inactive' }}</span>
        <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2"><div><dt class="text-stone-500">Region</dt><dd class="mt-2 font-semibold">{{ $profile->region }}</dd></div><div><dt class="text-stone-500">Phone</dt><dd class="mt-2 font-semibold">{{ $profile->phone }}</dd></div><div class="sm:col-span-2"><dt class="text-stone-500">Address</dt><dd class="mt-2 font-semibold">{{ $profile->address }}</dd></div></dl>
        @if(!$profile->is_active)<p class="mt-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">This profile is inactive. Contact an administrator before planning new shipments.</p>@endif
        <a class="btn-secondary mt-7 inline-flex" href="{{ route('distributor.shipments.index') }}">View shipments</a>
    </x-card>
@endsection
