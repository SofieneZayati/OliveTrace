@extends('layouts.admin')
@section('title', 'Mills')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">The OliveTrace oil mills</p>
            <h1 class="display-title text-4xl sm:text-5xl">Mills & capacity.</h1>
            <p class="mt-4 text-sm text-stone-500">Every huilerie on the platform, its region and extraction method.</p>
        </div>
        <span class="role-badge"><x-icon name="drop" class="h-4 w-4" />Mill directory</span>
    </div>
    <x-card>
        <form method="GET" action="{{ route('admin.mills.index') }}" class="mb-7 grid gap-4 border-b border-stone-100 pb-7 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_auto]">
            <div>
                <x-input-label for="search" value="Search mill, region or owner" />
                <x-text-input id="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Find a mill..." class="mt-2 w-full" />
            </div>
            <div>
                <x-input-label for="region" value="Region" />
                <select id="region" name="region" class="mt-2 w-full">
                    <option value="">All regions</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region }}" @selected(request('region') === $region)>{{ $region }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="extraction_type" value="Extraction type" />
                <select id="extraction_type" name="extraction_type" class="mt-2 w-full">
                    <option value="">All methods</option>
                    @foreach ($extractionTypes as $type)
                        <option value="{{ $type }}" @selected(request('extraction_type') === $type)>{{ str_replace('_', ' ', ucwords($type, '_')) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-4">
                <x-primary-button>Filter</x-primary-button>
                <a href="{{ route('admin.mills.index') }}" class="text-link py-3 text-stone-500">Reset</a>
            </div>
        </form>
        <div class="relative overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Registered oil mills</caption>
                <thead class="border-b border-stone-200 text-xs text-stone-500">
                    <tr><th scope="col" class="pb-4 pr-5 font-medium">Mill</th><th scope="col" class="pb-4 pr-5 font-medium">Region</th><th scope="col" class="pb-4 pr-5 font-medium">Extraction</th><th scope="col" class="pb-4 pr-5 font-medium">Capacity</th><th scope="col" class="pb-4 pr-5 font-medium">Owner account</th><th scope="col" class="pb-4 pr-5 font-medium">Contact</th><th scope="col" class="pb-4 font-medium">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($mills as $mill)
                        <tr class="border-b border-stone-100 transition-colors hover:bg-olive-50/40">
                            <td class="py-5 pr-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-50 text-xs font-semibold text-olive-700" aria-hidden="true">{{ mb_strtoupper(mb_substr($mill->name, 0, 1)) }}</span>
                                    <span class="whitespace-nowrap font-semibold">{{ $mill->name }}</span>
                                </div>
                            </td>
                            <td class="py-5 pr-5 text-stone-500">{{ $mill->region }}</td>
                            <td class="py-5 pr-5"><span class="rounded-md bg-stone-100 px-2.5 py-1 text-xs text-stone-600">{{ ucwords(str_replace('_', ' ', $mill->extraction_type)) }}</span></td>
                            <td class="py-5 pr-5 text-stone-500">{{ $mill->capacity ? number_format($mill->capacity).' kg/h' : '—' }}</td>
                            <td class="py-5 pr-5">
                                @if ($mill->user)
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs text-olive-700"><span class="h-1.5 w-1.5 rounded-full bg-olive-500"></span>{{ $mill->user->email }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs text-stone-500"><span class="h-1.5 w-1.5 rounded-full bg-stone-400"></span>Unknown owner</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap py-5 text-stone-500">{{ $mill->contact ?? '—' }}</td>
                            <td class="whitespace-nowrap py-5">
                                <div class="flex items-center gap-4">
                                    <a href="{{ route('admin.mills.show', $mill) }}" class="text-link text-xs">View<span class="sr-only"> {{ $mill->name }}</span></a>
                                    <a href="{{ route('admin.mills.edit', $mill) }}" class="text-link text-xs">Edit<span class="sr-only"> {{ $mill->name }}</span></a>
                                    <form method="POST" action="{{ route('admin.mills.destroy', $mill) }}" x-data @submit="if (!confirm('Delete this mill? Its owner account goes back to consumer.')) $event.preventDefault()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Delete<span class="sr-only"> {{ $mill->name }}</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-stone-500">No mills match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $mills->links() }}</div>
    </x-card>
@endsection
