@extends('layouts.front')
@section('title', 'My complaints')
@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div><p class="eyebrow mb-4 text-olive-600">After your purchase</p><h1 class="display-title text-4xl">Your complaints.</h1><p class="mt-4 text-sm text-stone-500">{{ $complaints->total() }} submitted complaint(s).</p></div>
            <a class="btn-primary" href="{{ route('complaints.create') }}">New complaint <x-icon name="arrow" /></a>
        </div>
        <div class="space-y-5">
            @forelse($complaints as $complaint)
                <x-card>
                    @php $dot = ['open' => 'bg-gold', 'in_review' => 'bg-olive-500', 'resolved' => 'bg-olive-800', 'rejected' => 'bg-stone-300'][$complaint->status->value] ?? 'bg-stone-300'; @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3"><p class="flex items-center gap-2"><span class="h-2 w-2 rounded-full {{ $dot }}"></span><span class="role-badge">{{ $complaint->status->label() }}</span></p><span class="text-xs text-stone-500">{{ $complaint->created_at?->toDateString() }} · {{ $complaint->product?->name ?? 'product #'.$complaint->oil_product_id }}</span></div>
                    <h2 class="font-display mt-3 text-2xl">{{ $complaint->subject }}</h2>
                    <p class="mt-2 line-clamp-2 text-sm text-stone-500">{{ $complaint->description }}</p>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 pt-4">
                        <a class="text-link" href="{{ route('complaints.show', $complaint) }}">Follow this complaint →</a>
                        <a class="text-link" href="{{ route('trace.show', $complaint->oil_product_id) }}">View product trace →</a>
                    </div>
                </x-card>
            @empty
                <x-card><h2 class="font-display text-2xl">No complaints submitted.</h2><p class="mt-3 text-sm text-stone-500">If a product disappointed you, describe the issue and our team will review it.</p></x-card>
            @endforelse
        </div>
        <div class="mt-8">{{ $complaints->links() }}</div>
    </div>
@endsection
