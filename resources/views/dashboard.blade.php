<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Welcome back, {{ Auth::user()->name }}</p>
        <h2 class="font-heading text-3xl font-bold sm:text-4xl">Your <span class="font-accent italic text-forest">research</span> dashboard</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:space-y-10 sm:px-6 sm:py-8">
        <div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-4">
            @foreach ($stats as $label => $value)
                <div class="card bg-sand">
                    <p class="text-xs font-medium text-gray-700 sm:text-sm">{{ $label }}</p>
                    <p class="mt-1 font-heading text-3xl font-bold text-forest sm:mt-2 sm:text-4xl">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        @if ($notifications->isNotEmpty())
            <div class="card">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-leaf/30 text-forest" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                        </span>
                        <h3 class="font-heading text-xl font-semibold">Updates</h3>
                        <span class="rounded-full bg-forest px-2 py-0.5 text-xs font-semibold text-cream">{{ $notifications->count() }} new</span>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="shrink-0 text-sm font-medium text-forest hover:underline">All notifications</a>
                </div>
                <ul class="mt-3 divide-y divide-forest/10">
                    @foreach ($notifications as $notification)
                        <li>
                            <a href="{{ $notification->data['url'] ?? '#' }}" class="group flex items-center gap-3 py-3">
                                <x-status-badge :status="$notification->data['status'] ?? 'pending'" class="hidden sm:inline-block" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium group-hover:text-forest">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="text-xs text-gray-600">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                                <svg class="h-4 w-4 shrink-0 text-gray-400 group-hover:text-forest" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_320px] [&>*]:min-w-0">
            <div class="card">
                <div class="flex items-center justify-between">
                    <h3 class="font-heading text-xl font-semibold">Recent sightings</h3>
                    <a href="{{ route('sightings.index') }}" class="text-sm font-medium text-forest hover:underline">View all</a>
                </div>
                @include('sightings._table', ['sightings' => $recent])
            </div>

            <div class="card hidden flex-col gap-3 bg-forest text-cream lg:flex">
                <h3 class="font-heading text-xl font-semibold">Quick actions</h3>
                <a href="{{ route('identifications.create') }}" class="rounded-lg bg-cream px-4 py-3 font-medium text-forest hover:bg-sand">Identify a plant</a>
                <a href="{{ route('sightings.create') }}" class="rounded-lg bg-cream/10 px-4 py-3 font-medium ring-1 ring-cream/30 hover:bg-cream/20">Log a sighting</a>
                @if ($reviewQueueCount !== null)
                    <a href="{{ route('review.index') }}" class="rounded-lg bg-cream/10 px-4 py-3 font-medium ring-1 ring-cream/30 hover:bg-cream/20">Review queue ({{ $reviewQueueCount }})</a>
                @endif
                @can('create', App\Models\Plant::class)
                    <a href="{{ route('plants.create') }}" class="rounded-lg bg-cream/10 px-4 py-3 font-medium ring-1 ring-cream/30 hover:bg-cream/20">Add a plant profile</a>
                @endcan
                <a href="{{ route('community.index') }}" class="rounded-lg bg-cream/10 px-4 py-3 font-medium ring-1 ring-cream/30 hover:bg-cream/20">Confirm community records</a>
                <a href="{{ route('map') }}" class="rounded-lg bg-cream/10 px-4 py-3 font-medium ring-1 ring-cream/30 hover:bg-cream/20">Sighting map</a>
            </div>
        </div>
        @if ($scans->isNotEmpty())
            <div class="card">
                <div class="flex items-center justify-between">
                    <h3 class="font-heading text-xl font-semibold">Recent scans</h3>
                    <a href="{{ route('identifications.index') }}" class="text-sm font-medium text-forest hover:underline">View all</a>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($scans as $identification)
                        @include('identifications._card')
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
