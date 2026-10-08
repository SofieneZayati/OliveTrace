@extends('layouts.admin')
@section('title', $product ? 'Edit product' : 'Create product')
@section('content')
    <div class="mb-8 max-w-2xl">
        <p class="eyebrow mb-4 text-olive-600">A product with a traceable origin</p>
        <h1 class="display-title text-4xl">{{ $product ? 'Refine your product.' : 'Add a product.' }}</h1>
        <p class="mt-4 text-sm leading-6 text-stone-500">Choose an oil lot. The same lot can supply several bottle sizes, and each product keeps its origin link.</p>
    </div>
    <x-card class="max-w-3xl">
        <x-validation-errors class="mb-6" />
        <form method="POST" enctype="multipart/form-data" action="{{ $product ? route($admin ? 'admin.products.update' : 'producer.products.update', $product) : route('producer.products.store') }}" class="space-y-6">
            @csrf
            @if($product) @method('PATCH') @endif
            <div class="grid gap-6 sm:grid-cols-2">
                <x-production-field name="name" label="Product name" :value="$product?->name" maxlength="120" :required="true" />
                <x-production-field name="brand" label="Brand" :value="$product?->brand" maxlength="120" :required="true" />
                <x-production-field name="oil_lot_id" label="Oil lot" :value="$product?->oil_lot_id" :options="collect($lots)->mapWithKeys(fn ($lot) => [$lot->id => $lot->lotCode.' - '.$lot->grade])->all()" :required="true" />
                <x-production-field name="bottle_volume_ml" label="Bottle volume (ml)" type="number" :value="$product?->bottle_volume_ml ?? 750" min="100" max="5000" :required="true" />
                <x-production-field name="packaging_date" label="Packaging date" type="date" :value="$product?->packaging_date?->format('Y-m-d')" :required="true" />
                <x-production-field name="public_status" label="Public status" :value="$product?->public_status?->value ?? 'visible'" :options="collect(\App\Enums\OilProductPublicStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
            </div>
            <div>
                <label for="image" class="form-label">Product image (optional)</label>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm">
                <p class="mt-2 text-xs text-stone-500">JPG, PNG, or WebP, up to 2 MB.</p>
                <x-input-error :messages="$errors->get('image')" class="mt-2" />
                @if($product?->image)<img class="mt-4 h-28 rounded-xl object-cover" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image) }}" alt="Current product image">@endif
            </div>
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6">
                <x-primary-button>{{ $product ? 'Save product' : 'Create product' }}</x-primary-button>
                <a class="btn-secondary" href="{{ $product ? route($admin ? 'admin.products.show' : 'producer.products.show', $product) : route('producer.products.index') }}">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
