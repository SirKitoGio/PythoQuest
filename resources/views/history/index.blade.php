<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-3xl font-bold sm:text-4xl">History &amp; <span class="font-accent italic text-forest">favorites</span></h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6">
        <section class="card">
            <h3 class="font-heading text-xl font-semibold">Favorite plants</h3>
            @forelse ($favorites as $plant)
                @if ($loop->first) <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"> @endif
                <a href="{{ route('plants.show', $plant) }}" class="rounded-lg bg-sand p-4 ring-1 ring-forest/10 hover:bg-leaf/20">
                    <p class="font-medium">{{ $plant->common_name }}</p>
                    <p class="text-sm italic text-gray-700">{{ $plant->scientific_name }}</p>
                </a>
                @if ($loop->last) </div> @endif
            @empty
                <p class="mt-3 text-sm text-gray-600">Save plants from their profile pages to find them here.</p>
            @endforelse
        </section>

        <section class="card">
            <h3 class="font-heading text-xl font-semibold">Recent scans</h3>
            @if ($identifications->isEmpty())
                <p class="mt-3 text-sm text-gray-600">No scans yet.</p>
            @else
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($identifications as $identification)
                        @include('identifications._card')
                    @endforeach
                </div>
            @endif
        </section>

        <div class="grid gap-8 lg:grid-cols-2">
            <section class="card">
                <div class="flex items-center justify-between">
                    <h3 class="font-heading text-xl font-semibold">Searches</h3>
                    @if ($searches->isNotEmpty())
                        <form method="POST" action="{{ route('history.searches.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm font-medium text-gray-600 hover:text-red-700">Clear</button>
                        </form>
                    @endif
                </div>
                <ul class="mt-3 divide-y divide-forest/10 text-sm">
                    @forelse ($searches as $search)
                        <li class="flex items-center justify-between py-2">
                            <a href="{{ route('plants.index', array_filter(['q' => $search->query] + ($search->filters ?? []))) }}" class="font-medium text-forest hover:underline">
                                {{ $search->query ?? 'Filtered browse' }}
                                @if ($search->filters)
                                    <span class="font-normal text-gray-600">({{ collect($search->filters)->map(fn ($value, $key) => "{$key}: {$value}")->implode(', ') }})</span>
                                @endif
                            </a>
                            <span class="text-gray-600">{{ $search->results_count }} results &middot; {{ $search->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-gray-600">No searches yet.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card">
                <h3 class="font-heading text-xl font-semibold">Confirmations given</h3>
                <ul class="mt-3 divide-y divide-forest/10 text-sm">
                    @forelse ($confirmations as $confirmation)
                        <li class="flex items-center justify-between py-2">
                            <span>{{ $confirmation->sighting->plant->common_name }} &middot; {{ $confirmation->sighting->observed_on->format('M j, Y') }}</span>
                            <x-status-badge :status="$confirmation->sighting->status" />
                        </li>
                    @empty
                        <li class="py-2 text-gray-600">No confirmations yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <section class="card">
            <div class="flex items-center justify-between">
                <h3 class="font-heading text-xl font-semibold">Submissions</h3>
                <a href="{{ route('sightings.index') }}" class="text-sm font-medium text-forest hover:underline">View all</a>
            </div>
            @include('sightings._table', ['sightings' => $sightings])
        </section>
    </div>
</x-app-layout>
