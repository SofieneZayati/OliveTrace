@extends('layouts.front')
@section('title', 'Account')
@section('content')
    <div class="mx-auto max-w-md">
        <x-card>
            {{ $slot }}
        </x-card>
    </div>
@endsection
