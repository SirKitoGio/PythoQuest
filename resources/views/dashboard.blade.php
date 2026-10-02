<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Welcome back, {{ Auth::user()->name }}</p>
        <h2 class="font-heading text-4xl font-bold">Your <span class="font-accent italic text-forest">research</span> dashboard</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-10 px-4 py-8 sm:px-6">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $label => $value)
                <div class="card bg-sand">
                    <p class="text-sm font-medium text-gray-700">{{ $label }}</p>
                    <p class="mt-2 font-heading text-4xl font-bold text-forest">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div class="card">
                <div class="flex items-center justify-between">
                    <h3 class="font-heading text-xl font-semibold">Recent sightings</h3>
                    <a href="{{ route('sightings.index') }}" class="text-sm font-medium text-forest hover:underline">View all</a>
                </div>
                @include('sightings._table', ['sightings' => $recent])
            </div>

            <div class="card flex flex-col gap-3 bg-forest text-cream">
                <h3 class="font-heading text-xl font-semibold">Quick actions</h3>
                <a href="{{ route('sightings.create') }}" class="rounded-lg bg-cream px-4 py-3 font-medium text-forest hover:bg-sand">Log a sighting</a>
                <a href="{{ route('plants.create') }}" class="rounded-lg bg-cream/10 px-4 py-3 font-medium ring-1 ring-cream/30 hover:bg-cream/20">Add a plant profile</a>
                <a href="{{ route('plants.index') }}" class="rounded-lg bg-cream/10 px-4 py-3 font-medium ring-1 ring-cream/30 hover:bg-cream/20">Browse plants</a>
            </div>
        </div>
    </div>
</x-app-layout>
