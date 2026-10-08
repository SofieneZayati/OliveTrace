@extends('layouts.admin')
@section('title', 'Consumer reviews')
@section('content')
    <div class="mb-8"><p class="eyebrow mb-4 text-olive-600">Post-purchase trust</p><h1 class="display-title text-4xl">Moderate reviews.</h1><p class="mt-4 text-sm text-stone-500">{{ $feedback->total() }} review(s). Hide inappropriate content; never edit a consumer's words.</p></div>
    <x-card class="mb-7"><form method="GET" class="flex flex-wrap items-end gap-4">
        <div><x-input-label for="status" value="Visibility" /><select id="status" name="status" class="mt-1 block w-48 rounded-lg border-stone-300"><option value="">All</option>@foreach(\App\Enums\Consumer\FeedbackStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
        <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route('admin.consumer.feedback.index') }}">Reset</a></div>
    </form></x-card>
    <div class="space-y-5">
        @forelse($feedback as $item)
            <x-card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="star" class="h-4 w-4 text-olive-600" />{{ $item->rating }} / 5 <span class="font-normal text-stone-500">· {{ $item->consumer?->name }} ({{ $item->consumer?->email }}) · {{ $item->product?->name ?? 'product #'.$item->oil_product_id }}</span></p>
                    <span class="role-badge">{{ $item->status->label() }}</span>
                </div>
                @if($item->comment)<p class="mt-3 text-sm leading-6">{{ $item->comment }}</p>@endif
                <p class="mt-3 text-xs text-stone-500">Assistant suggestion: {{ $item->ai_category?->label() ?? '—' }} · {{ $item->ai_sentiment?->label() ?? '—' }} · {{ $item->ai_priority?->label() ?? '—' }} priority (advisory only).</p>
                <form method="POST" action="{{ route('admin.consumer.feedback.update', $item) }}" class="mt-4 flex items-center gap-3">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $item->status === \App\Enums\Consumer\FeedbackStatus::Visible ? 'hidden' : 'visible' }}">
                    <button type="submit" class="btn-secondary py-2">{{ $item->status === \App\Enums\Consumer\FeedbackStatus::Visible ? 'Hide review' : 'Show review' }}</button>
                </form>
            </x-card>
        @empty
            <x-card><p class="text-sm text-stone-500">No reviews to moderate.</p></x-card>
        @endforelse
    </div>
    <div class="mt-8">{{ $feedback->links() }}</div>
@endsection
