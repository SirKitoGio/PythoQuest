<x-site-layout :title="$plant->common_name">
    <section class="mx-auto max-w-6xl px-6 pt-14">
        <a href="{{ route('plants.index') }}" class="text-sm font-medium text-forest hover:underline">&larr; All plants</a>

        <div class="mt-6 grid gap-10 lg:grid-cols-[1fr_380px]">
            <div>
                <span class="badge">{{ $plant->category }}</span>
                <h1 class="mt-3 font-heading text-5xl font-bold">{{ $plant->common_name }}</h1>
                <p class="mt-2 font-accent text-2xl italic text-forest">{{ $plant->scientific_name }}</p>

                <dl class="mt-10 grid gap-6 sm:grid-cols-2">
                    @foreach ([
                        'Habitat' => $plant->habitat,
                        'Plant parts studied' => $plant->plant_parts,
                        'Physical features' => $plant->physical_features,
                        'Known toxins' => $plant->known_toxins ?? 'None recorded',
                        'Documented extract types' => $plant->extract_types ?? 'None recorded',
                        'Research reference' => $plant->reference ?? 'None recorded',
                    ] as $label => $value)
                        <div class="card bg-sand">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-forest">{{ $label }}</dt>
                            <dd class="mt-2 text-gray-800">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <p class="mt-6 text-sm text-gray-600">
                    Source: <span class="font-medium text-gray-800">{{ $plant->source_label }}</span>
                    &middot; Last updated {{ $plant->updated_at->format('M j, Y') }}
                </p>
                <p class="mt-4 rounded-lg bg-sand px-4 py-3 text-sm text-gray-700 ring-1 ring-forest/10">
                    This profile supports research only. It is not medical advice and does not recommend dosage, preparation or consumption.
                </p>
            </div>

            <aside class="space-y-6">
                <x-plant-image :plant="$plant" class="h-72 rounded-xl shadow-sm" />

                <div class="card">
                    <h2 class="font-heading text-lg font-semibold">Trusted sightings</h2>
                    @forelse ($sightings as $sighting)
                        <div class="mt-3 border-t border-forest/10 pt-3 text-sm">
                            <p class="font-medium">{{ $sighting->visibility === 'public' ? ($sighting->location_name ?? 'Pinned location') : 'Generalized area (protected record)' }}</p>
                            <p class="text-gray-600">{{ $sighting->observed_on->format('M j, Y') }} &middot; {{ $sighting->plant_part }}</p>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-gray-600">No reviewed sightings yet.</p>
                    @endforelse
                </div>

                @auth
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('sightings.create', ['plant' => $plant->id]) }}" class="btn-primary">Log a sighting</a>
                        <a href="{{ route('plants.edit', $plant) }}" class="btn-secondary">Edit</a>
                        <form method="POST" action="{{ route('plants.destroy', $plant) }}" onsubmit="return confirm('Delete this plant profile and all of its sightings?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-medium text-red-700 ring-1 ring-red-700/30 hover:bg-red-50">Delete</button>
                        </form>
                    </div>
                @endauth
            </aside>
        </div>
    </section>
</x-site-layout>
