@extends('layouts.admin')
@section('title', 'Mill requests')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Your mill</p>
            <h1 class="display-title text-4xl">Incoming requests</h1>
            <p class="mt-4 text-sm text-stone-500">Manage harvests sent to your mill.</p>
        </div>
    </div>
    <x-card>
        <form method="GET" class="mb-6 grid items-end gap-4 sm:grid-cols-3">
            <x-production-field name="status" label="Status" :value="request('status')" :options="['pending' => 'Pending', 'accepted' => 'Accepted', 'refused' => 'Refused', 'completed' => 'Completed', 'cancelled' => 'Cancelled']" />
            <x-production-field name="search" label="Search harvest, farm or producer" :value="request('search')" maxlength="100" />
            <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route('mill.mill-requests.index') }}">Reset</a></div>
        </form>
        @forelse($requests as $request)
            <div class="mt-6 flex flex-wrap items-start justify-between gap-4 border-t border-stone-100 pt-6 first:mt-0 first:border-0 first:pt-0">
                <div class="grid gap-4 sm:grid-cols-4">
                    <div><p class="text-xs text-stone-500">Harvest</p><p class="mt-2 text-sm font-semibold"><a class="text-link" href="{{ route('mill.mill-requests.show', $request->id) }}">#{{ $request->harvest->id }} · {{ $request->harvest->quantity_kg }} kg</a></p></div>
                    <div><p class="text-xs text-stone-500">Farm / Producer</p><p class="mt-2 text-sm font-semibold">{{ $request->harvest->farm->name }}<br><span class="text-stone-500 font-normal">{{ $request->harvest->farm->producerProfile->display_name }}</span></p></div>
                    <div><p class="text-xs text-stone-500">Requested for</p><p class="mt-2 text-sm font-semibold">{{ $request->requested_date->isoFormat('ll') }}</p></div>
                    <div><p class="text-xs text-stone-500">Status</p><p class="mt-2 font-semibold"><span class="role-badge">{{ $request->status->label() }}</span></p></div>
                </div>
                <div class="flex items-center gap-3"><a class="btn-secondary" href="{{ route('mill.mill-requests.show', $request->id) }}">Open</a></div>
            </div>
        @empty
            <p class="mt-3 text-sm leading-6 text-stone-500">No mill requests yet.</p>
        @endforelse
        <div class="mt-8">{{ $requests->links() }}</div>
    </x-card>
@endsection