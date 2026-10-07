@extends('layouts.admin')
@section('title', 'Consumer complaints')
@section('content')
    <div class="mb-8"><p class="eyebrow mb-4 text-olive-600">Post-purchase trust</p><h1 class="display-title text-4xl">Handle complaints.</h1><p class="mt-4 text-sm text-stone-500">{{ $complaints->total() }} complaint(s). Review, respond, then set the status.</p></div>
    <x-card class="mb-7"><form method="GET" class="flex flex-wrap items-end gap-4">
        <div><x-input-label for="status" value="Status" /><select id="status" name="status" class="mt-1 block w-48 rounded-lg border-stone-300"><option value="">All</option>@foreach(\App\Enums\Consumer\ComplaintStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
        <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route('admin.consumer.complaints.index') }}">Reset</a></div>
    </form></x-card>
    <div class="space-y-5">
        @forelse($complaints as $complaint)
            <x-card>
                <div class="flex flex-wrap items-center justify-between gap-3"><span class="role-badge">{{ $complaint->status->label() }}</span><span class="text-xs text-stone-500">{{ $complaint->created_at?->toDateString() }} · product #{{ $complaint->oil_product_id }}</span></div>
                <h2 class="font-display mt-3 text-2xl">{{ $complaint->subject }}</h2>
                <p class="mt-2 text-sm text-stone-500">{{ $complaint->consumer?->name }} ({{ $complaint->consumer?->email }}) · AI priority: {{ $complaint->ai_priority?->label() ?? '—' }}</p>
                <a class="text-link mt-4 flex items-center justify-between" href="{{ route('admin.consumer.complaints.show', $complaint) }}">Review and respond <x-icon name="arrow" /></a>
            </x-card>
        @empty
            <x-card><p class="text-sm text-stone-500">No complaints to handle.</p></x-card>
        @endforelse
    </div>
    <div class="mt-8">{{ $complaints->links() }}</div>
@endsection
