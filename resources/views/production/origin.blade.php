@extends('layouts.front')
@section('title', 'Farm origin')
@section('content')
    <div class="mx-auto max-w-3xl"><p class="eyebrow mb-4 text-olive-600">The beginning of the olive story</p><h1 class="display-title mb-4 text-4xl sm:text-5xl">Know where it grows.</h1><p class="mb-8 text-sm leading-7 text-stone-500">Discover the selected origin information behind this farm.</p><x-origin-card :origin="$origin" /></div>
@endsection
