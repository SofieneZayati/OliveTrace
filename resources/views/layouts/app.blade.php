@extends(auth()->user()->role === \App\Enums\Role::Consumer ? 'layouts.front' : 'layouts.admin')
@section('title', $title)
@section('content')
    @isset($header)
        <div class="mb-6">{{ $header }}</div>
    @endisset
    {{ $slot }}
@endsection
