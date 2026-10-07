@extends('layouts.front')
@section('title', 'Edit my review')
@section('content')
    <div class="mx-auto max-w-2xl">
        <p class="eyebrow mb-4 text-olive-600">Your review</p>
        <h1 class="display-title mb-8 text-4xl">Keep it accurate.</h1>
        <x-card>
            @include('consumer.feedback._form', ['feedback' => $feedback, 'productId' => $feedback->oil_product_id])
        </x-card>
    </div>
@endsection
