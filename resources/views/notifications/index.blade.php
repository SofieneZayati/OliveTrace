@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Updates and actions</p>
            <h1 class="display-title text-4xl">Notifications</h1>
            <p class="mt-4 text-sm text-stone-500">Follow the latest activity on your requests and milling operations.</p>
        </div>
        @if(auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary">Mark all as read</button>
            </form>
        @endif
    </div>

    <x-card>
        @forelse($notifications as $notification)
            @php($data = $notification->data)
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-stone-100 py-5 first:pt-0 last:border-b-0 last:pb-0">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        @unless($notification->read_at)
                            <span class="h-2 w-2 shrink-0 rounded-full bg-olive-600" title="Unread"></span>
                        @endunless
                        <h2 class="font-semibold text-olive-900">{{ $data['title'] ?? 'Notification' }}</h2>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-stone-600">{{ $data['message'] ?? '' }}</p>
                    <p class="mt-2 text-xs text-stone-400">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                <a class="btn-secondary shrink-0" href="{{ route('notifications.read', $notification->id) }}">Open</a>
            </div>
        @empty
            <p class="text-sm text-stone-500">You have no notifications yet.</p>
        @endforelse

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </x-card>
@endsection
