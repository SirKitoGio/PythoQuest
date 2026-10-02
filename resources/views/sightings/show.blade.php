<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('sightings.index') }}" class="text-sm font-medium text-forest hover:underline">&larr; My sightings</a>
        <div class="mt-3 flex flex-wrap items-center gap-4">
            <h2 class="font-heading text-4xl font-bold">{{ $sighting->plant->common_name }}</h2>
            <x-status-badge :status="$sighting->status" />
        </div>
        <p class="mt-1 font-accent text-xl italic text-forest">{{ $sighting->plant->scientific_name }}</p>
    </x-slot>

    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[380px_1fr]">
        <x-plant-image :plant="$sighting->plant" :src="$sighting->photo_path ? Storage::url($sighting->photo_path) : null" class="h-80 rounded-xl shadow-sm" />

        <div class="card bg-sand">
            <dl class="grid gap-6 sm:grid-cols-2">
                @foreach ([
                    'Observed on' => $sighting->observed_on->format('F j, Y'),
                    'Plant part' => ucfirst($sighting->plant_part),
                    'Location' => $sighting->location_name ?? 'Pinned location',
                    'Coordinates' => number_format($sighting->latitude, 5).', '.number_format($sighting->longitude, 5),
                    'Location status' => ucfirst($sighting->visibility),
                    'Submitted' => $sighting->created_at->diffForHumans(),
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-forest">{{ $label }}</dt>
                        <dd class="mt-1 text-gray-800">{{ $value }}</dd>
                    </div>
                @endforeach
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-forest">Notes</dt>
                    <dd class="mt-1 text-gray-800">{{ $sighting->notes ?? 'No notes.' }}</dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-wrap gap-3 border-t border-forest/10 pt-6">
                <a href="https://www.openstreetmap.org/?mlat={{ $sighting->latitude }}&mlon={{ $sighting->longitude }}#map=18/{{ $sighting->latitude }}/{{ $sighting->longitude }}"
                    target="_blank" rel="noopener" class="btn-secondary">View on map</a>
                <a href="{{ route('sightings.edit', $sighting) }}" class="btn-secondary">Edit</a>
                <form method="POST" action="{{ route('sightings.destroy', $sighting) }}" onsubmit="return confirm('Delete this sighting?')">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-medium text-red-700 ring-1 ring-red-700/30 hover:bg-red-50">Delete</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
