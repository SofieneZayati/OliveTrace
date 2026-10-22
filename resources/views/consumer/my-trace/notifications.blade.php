@extends('layouts.front')
@section('title', 'Mes notifications')
@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div><p class="eyebrow mb-4 text-olive-600">Centre de notifications</p><h1 class="display-title text-4xl">Toutes vos alertes.</h1><p class="mt-4 text-sm text-stone-500">{{ $notifications->total() }} notification(s).</p></div>
            @if($notifications->where('read', false)->count() > 0)
                <form method="POST" action="{{ route('my-trace.notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn-primary">Tout marquer comme lu</button>
                </form>
            @endif
        </div>

        <div class="space-y-4">
            @forelse($notifications as $notification)
                <x-card class="{{ $notification->read ? 'opacity-60' : 'ring-2 ring-olive-500' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-sm">{{ $notification->title }}</p>
                            <p class="mt-1 text-sm text-stone-500">{{ $notification->message }}</p>
                            <p class="mt-1 text-xs text-stone-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @unless($notification->read)
                            <form method="POST" action="{{ route('my-trace.notifications.read', $notification) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs text-link">Marquer comme lu</button>
                            </form>
                        @endunless
                    </div>
                </x-card>
            @empty
                <x-card><p class="text-sm text-stone-500">Aucune notification.</p></x-card>
            @endforelse
        </div>

        <div class="mt-8">{{ $notifications->links() }}</div>
    </div>
@endsection
