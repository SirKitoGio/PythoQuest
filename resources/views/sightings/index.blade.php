<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="font-heading text-4xl font-bold">My <span class="font-accent italic text-forest">sightings</span></h2>
            <a href="{{ route('sightings.create') }}" class="btn-primary">+ Log a sighting</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="card">
            @include('sightings._table')
            <div class="mt-6">{{ $sightings->links() }}</div>
        </div>
    </div>
</x-app-layout>
