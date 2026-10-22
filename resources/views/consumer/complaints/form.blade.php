@extends('layouts.front')
@section('title', 'Submit a complaint')
@section('content')
    <div class="mx-auto max-w-2xl">
        <p class="eyebrow mb-4 text-olive-600">Your voice matters</p>
        <h1 class="display-title mb-4 text-4xl">Something wrong? Tell us.</h1>
        <p class="mb-8 text-sm leading-6 text-stone-500">Describe the issue precisely. An automatic assistant suggests a category and priority to help our team, but a human reviews every complaint.</p>
        <x-card>
            <x-validation-errors class="mb-4" />
            <form method="POST" action="{{ route('complaints.store') }}" class="space-y-6" enctype="multipart/form-data">
                @csrf
                <div>
                    <x-input-label for="oil_product_id" value="Concerned product *" />
                    @if($products->isNotEmpty())
                        <select id="oil_product_id" name="oil_product_id" required class="mt-1 block w-full rounded-lg border-stone-300">
                            <option value="">Select the product…</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((int) old('oil_product_id', request('product')) === $product->id)>{{ $product->name }} · {{ $product->brand }}</option>
                            @endforeach
                        </select>
                    @else
                        <x-text-input id="oil_product_id" name="oil_product_id" type="number" min="1" class="mt-1 block w-40" :value="old('oil_product_id', request('product'))" required />
                        <p class="mt-2 text-xs text-stone-500">The product number shown on the trace page.</p>
                    @endif
                    <x-input-error :messages="$errors->get('oil_product_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="subject" value="Subject *" />
                    <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject')" maxlength="150" required placeholder="e.g. Bottle arrived leaking" />
                    <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description *" />
                    <textarea id="description" name="description" rows="5" maxlength="3000" required placeholder="What happened, when, and what you observed…" class="mt-1 block w-full rounded-lg border-stone-300">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="evidence_photo" value="Photo preuve (optionnel)" />
                    <p class="mb-2 text-xs text-stone-500">Joignez une photo du problème : bouchon cassé, emballage endommagé, huile trouble… (JPG, PNG ou WebP, max 2 Mo)</p>
                    <input id="evidence_photo" name="evidence_photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-stone-500 file:mr-4 file:rounded-lg file:border-0 file:bg-olive-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-olive-700 hover:file:bg-olive-100" />
                    <x-input-error :messages="$errors->get('evidence_photo')" class="mt-2" />
                </div>
                <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><x-primary-button>Submit my complaint</x-primary-button><a class="btn-secondary" href="{{ route('complaints.index') }}">Cancel</a></div>
            </form>
        </x-card>
    </div>
@endsection
