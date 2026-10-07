@extends('layouts.front')
@section('title', 'Trace not available')
@section('content')
    <div class="mx-auto max-w-2xl text-center">
        <p class="eyebrow mb-4 text-olive-600">Product traceability</p>
        <h1 class="display-title mb-4 text-4xl">This trace is not available yet.</h1>
        <x-card>
            <p class="text-sm leading-7 text-stone-500">No public product matches <span class="font-semibold text-stone-700">{{ $slug }}</span>. The product may not be published yet, or its upstream modules (harvest, certification, distribution) may still be in progress.</p>
            <p class="mt-4 text-sm leading-7 text-stone-500">If you scanned a QR code, please check the code or ask the seller for the correct trace link.</p>
            <a class="btn-primary mt-6 inline-block" href="{{ route('home') }}">Back to home</a>
        </x-card>
    </div>
@endsection
