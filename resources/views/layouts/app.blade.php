@extends(auth()->user()->role === \App\Enums\Role::Consumer ? 'layouts.front' : 'layouts.admin')
@section('title', 'Profile')
@section('content')
    @isset($header)
        <div class="mb-6">{{ $header }}</div>
    @endisset
    {{ $slot }}
@endsection
