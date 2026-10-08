@extends('layouts.admin')
@section('title', $admin ? 'All products' : 'My products')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">From the olive grove to the bottle</p>
            <h1 class="display-title text-4xl">{{ $admin ? 'Products across OliveTrace.' : 'Your olive oil products.' }}</h1>
            <p class="mt-4 text-sm text-stone-500">{{ $products->total() }} products {{ $admin ? 'across all producers' : 'in your workspace' }}.</p>
        </div>
        @unless($admin)<a class="btn-primary" href="{{ route('producer.products.create') }}">Add a product <x-icon name="arrow" /></a>@endunless
    </div>
    <x-card class="mb-7">
        <form method="GET" class="grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <x-production-field name="status" label="Public status" :value="$filters['status'] ?? ''" :options="collect(\App\Enums\OilProductPublicStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
            <x-production-field name="date_from" label="Packaged from" type="date" :value="$filters['date_from'] ?? ''" />
            <x-production-field name="date_to" label="Packaged to" type="date" :value="$filters['date_to'] ?? ''" />
            @if($admin)<x-production-field name="owner_id" label="Producer user ID" type="number" :value="$filters['owner_id'] ?? ''" min="1" />@endif
            <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route($admin ? 'admin.products.index' : 'producer.products.index') }}">Reset</a></div>
        </form>
    </x-card>
    <x-card class="overflow-x-auto p-0">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-stone-100 bg-stone-50/70 text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-5 py-4">Product</th><th class="px-5 py-4">Brand</th><th class="px-5 py-4">Bottle</th><th class="px-5 py-4">Packaged</th><th class="px-5 py-4">Visibility</th><th class="px-5 py-4">Producer</th><th class="px-5 py-4"></th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse($products as $product)
                    <tr>
                        <td class="px-5 py-4"><span class="font-semibold">{{ $product->name }}</span>@if($product->archived_at)<span class="ml-2 text-xs text-stone-500">Archived</span>@endif</td>
                        <td class="px-5 py-4">{{ $product->brand }}</td><td class="px-5 py-4">{{ $product->bottle_volume_ml }} ml</td><td class="px-5 py-4">{{ $product->packaging_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-4"><span class="role-badge">{{ $product->public_status->label() }}</span></td>
                        <td class="px-5 py-4">{{ $product->createdBy?->name ?? 'Unknown' }}</td>
                        <td class="px-5 py-4 text-right"><a class="text-link" href="{{ route($admin ? 'admin.products.show' : 'producer.products.show', $product) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center"><h2 class="font-display text-2xl">No products to show yet.</h2><p class="mt-3 text-sm text-stone-500">Try different filters{{ $admin ? '.' : ' or add your first product.' }}</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
    <div class="mt-8">{{ $products->links() }}</div>
@endsection
