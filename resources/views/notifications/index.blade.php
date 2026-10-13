<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="font-heading text-3xl font-bold sm:text-4xl">Notifications</h2>
            @if (Auth::user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.update') }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn-secondary">Mark all as read</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <div class="card divide-y divide-forest/10 p-0">
            @forelse ($notifications as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}" @class(['block px-6 py-4 hover:bg-sand/60', 'bg-leaf/10' => ! $notification->read_at])>
                    <div class="flex items-start justify-between gap-4">
                        <p @class(['text-gray-900', 'font-semibold' => ! $notification->read_at])>{{ $notification->data['title'] ?? 'Notification' }}</p>
                        <span class="shrink-0 text-xs text-gray-600">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-700">{{ $notification->data['body'] ?? '' }}</p>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-gray-700">You're all caught up.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
