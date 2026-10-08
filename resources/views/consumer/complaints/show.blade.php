@extends('layouts.front')
@section('title', 'Complaint status')
@section('content')
    <div class="mx-auto max-w-2xl">
        <p class="eyebrow mb-4 text-olive-600">Complaint #{{ $complaint->id }} · {{ $complaint->product?->name ?? 'product #'.$complaint->oil_product_id }}</p>
        <h1 class="display-title mb-8 text-4xl">{{ $complaint->subject }}</h1>
        <x-card class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3"><span class="role-badge">{{ $complaint->status->label() }}</span><span class="text-xs text-stone-500">{{ $complaint->created_at?->toDateString() }}</span></div>
            <p class="mt-5 text-sm leading-7">{{ $complaint->description }}</p>
            @if($complaint->admin_response)
                <div class="mt-6 rounded-xl bg-olive-50 p-5"><p class="eyebrow mb-2 text-olive-600">Team response</p><p class="text-sm leading-7">{{ $complaint->admin_response }}</p></div>
            @else
                <p class="mt-6 text-sm text-stone-500">Our team has not responded yet. You will see the response here.</p>
            @endif
            @if($complaint->resolved_at)<p class="mt-4 text-xs text-stone-500">Closed on {{ $complaint->resolved_at->toDateString() }}.</p>@endif
        </x-card>
        <a class="text-link" href="{{ route('complaints.index') }}">← Back to my complaints</a>
    </div>
@endsection
