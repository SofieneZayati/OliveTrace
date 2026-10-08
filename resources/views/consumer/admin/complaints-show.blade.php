@extends('layouts.admin')
@section('title', 'Complaint #'.$complaint->id)
@section('content')
    <div class="mx-auto max-w-3xl">
        <p class="eyebrow mb-4 text-olive-600">Complaint #{{ $complaint->id }} · {{ $complaint->product?->name ?? 'product #'.$complaint->oil_product_id }}</p>
        <h1 class="display-title mb-8 text-4xl">{{ $complaint->subject }}</h1>
        <x-card class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3"><span class="role-badge">{{ $complaint->status->label() }}</span><span class="text-xs text-stone-500">{{ $complaint->consumer?->name }} ({{ $complaint->consumer?->email }}) · {{ $complaint->created_at?->toDateString() }}</span></div>
            <p class="mt-5 text-sm leading-7">{{ $complaint->description }}</p>
        </x-card>
        <x-card class="mb-6">
            <p class="eyebrow mb-3 text-olive-600">Assistant suggestion (advisory only)</p>
            <p class="text-sm text-stone-500">Category: <span class="font-semibold text-stone-700">{{ $complaint->ai_category?->label() ?? '—' }}</span> · Sentiment: <span class="font-semibold text-stone-700">{{ $complaint->ai_sentiment?->label() ?? '—' }}</span> · Suggested priority: <span class="font-semibold text-stone-700">{{ $complaint->ai_priority?->label() ?? '—' }}</span>.</p>
            <p class="mt-2 text-xs leading-5 text-stone-500">The classifier detects explicit keywords only; it cannot judge irony or mixed topics. You confirm every decision below.</p>
        </x-card>
        <x-card>
            <x-validation-errors class="mb-4" />
            <form method="POST" action="{{ route('admin.consumer.complaints.update', $complaint) }}" class="space-y-6">
                @csrf @method('PATCH')
                <div>
                    <x-input-label for="status" value="Decision *" />
                    <select id="status" name="status" required class="mt-1 block w-64 rounded-lg border-stone-300">
                        @foreach(['in_review' => 'In review', 'resolved' => 'Resolved', 'rejected' => 'Rejected'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $complaint->status->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="admin_response" value="Response to the consumer" />
                    <textarea id="admin_response" name="admin_response" rows="4" maxlength="3000" class="mt-1 block w-full rounded-lg border-stone-300">{{ old('admin_response', $complaint->admin_response) }}</textarea>
                    <x-input-error :messages="$errors->get('admin_response')" class="mt-2" />
                </div>
                <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><x-primary-button>Save decision</x-primary-button><a class="btn-secondary" href="{{ route('admin.consumer.complaints.index') }}">Back to list</a></div>
            </form>
        </x-card>
    </div>
@endsection
