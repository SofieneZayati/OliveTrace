@extends('layouts.admin')
@section('title', 'Producers')
@section('content')
    <p class="eyebrow mb-4 text-olive-600">Origin oversight</p>
    <h1 class="display-title mb-8 text-4xl">The people behind every farm.</h1>
    <x-card>
        <form method="GET" class="mb-7 grid items-end gap-4 sm:grid-cols-[1fr_1fr_auto]">
            <x-production-field name="search" label="Search producer or company" :value="request('search')" maxlength="100" />
            <x-production-field name="active" label="Profile status" :value="request('active')" :options="['1' => 'Enabled', '0' => 'Disabled']" />
            <div class="flex items-center gap-3"><button type="submit" class="btn-primary">Filter</button><a class="text-link" href="{{ route('admin.producers.index') }}">Reset</a></div>
        </form>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><caption class="sr-only">Producer profiles</caption><thead class="border-b border-stone-200 text-stone-500"><tr><th class="py-4 pr-4" scope="col">Producer</th><th class="py-4 pr-4" scope="col">Company</th><th class="py-4 pr-4" scope="col">Status</th><th class="py-4" scope="col">Details</th></tr></thead><tbody>
        @forelse($profiles as $profile)<tr class="border-b border-stone-100"><td class="py-5 pr-4 font-semibold">{{ $profile->displayName }}</td><td class="py-5 pr-4">{{ $profile->companyName ?? '—' }}</td><td class="py-5 pr-4">{{ $profile->isActive ? 'Enabled' : 'Disabled' }}</td><td class="py-5"><a class="text-link" href="{{ route('admin.producers.show', $profile->id) }}">View producer<span class="sr-only"> {{ $profile->displayName }}</span></a></td></tr>
        @empty<tr><td colspan="4" class="py-12 text-center text-stone-500">No producers match your filters.</td></tr>@endforelse
        </tbody></table></div><div class="mt-6">{{ $profiles->links() }}</div>
    </x-card>
@endsection
