<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Community</p>
        <h2 class="font-heading text-3xl font-bold sm:text-4xl">Confirm field <span class="font-accent italic text-forest">records</span></h2>
        <p class="mt-2 max-w-3xl text-gray-700">If you have independently seen the same plant, add your confirmation. Confirmations are supporting evidence for reviewers — they never approve a record on their own.</p>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @if ($sightings->isEmpty())
            <p class="py-16 text-center text-gray-700">No open records from other researchers yet.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($sightings as $sighting)
                    @php($location = $sighting->locationFor(Auth::user()))
                    <article class="overflow-hidden rounded-xl bg-sand shadow-sm ring-1 ring-forest/10">
                        <x-plant-image :plant="$sighting->plant" :src="$sighting->photoUrl()" class="h-44" />
                        <div class="p-5 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-heading text-xl font-semibold">{{ $sighting->plant->common_name }}</h3>
                                    <p class="italic text-gray-700">{{ $sighting->plant->scientific_name }}</p>
                                </div>
                                <x-status-badge :status="$sighting->status" />
                            </div>
                            <p class="mt-2 text-gray-700">
                                {{ ucfirst($sighting->plant_part) }} &middot; {{ $sighting->observed_on->format('M j, Y') }} &middot; by {{ $sighting->user->name }}
                            </p>
                            <p class="text-gray-600">{{ $location && $location['precision'] === 'exact' ? ($location['location_name'] ?? 'Pinned location') : 'Generalized area (~1 km)' }}</p>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-gray-700">{{ $sighting->confirmations_count }} {{ Str::plural('confirmation', $sighting->confirmations_count) }}</span>
                                <form method="POST" action="{{ route('sightings.confirmation.'.($sighting->confirmed_by_me ? 'destroy' : 'store'), $sighting) }}">
                                    @csrf
                                    @if ($sighting->confirmed_by_me)
                                        @method('DELETE')
                                        <button class="btn-secondary py-1.5 text-xs">Withdraw</button>
                                    @else
                                        <button class="btn-primary py-1.5 text-xs">I've seen this too</button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-8">{{ $sightings->links() }}</div>
        @endif
    </div>
</x-app-layout>
