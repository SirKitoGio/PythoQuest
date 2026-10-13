<x-site-layout :title="$plant->common_name">
    <section class="mx-auto max-w-6xl px-4 pt-8 sm:px-6 sm:pt-14">
        <a href="{{ route('plants.index') }}" class="text-sm font-medium text-forest hover:underline">&larr; All plants</a>

        <div class="mt-4 grid gap-8 sm:mt-6 lg:grid-cols-[1fr_380px] lg:gap-10 [&>*]:min-w-0">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge">{{ $plant->category }}</span>
                    @if ($plant->family)
                        <span class="badge bg-sand ring-1 ring-forest/20">{{ $plant->family }}</span>
                    @endif
                    <span @class([
                        'inline-block rounded-md px-3 py-0.5 text-xs font-medium',
                        'bg-purple-100 text-purple-800' => $plant->isSensitive(),
                        'bg-white/70 text-gray-700 ring-1 ring-forest/10' => ! $plant->isSensitive(),
                    ])>IUCN: {{ App\Models\Plant::CONSERVATION_STATUSES[$plant->conservation_status] ?? $plant->conservation_status }}</span>
                </div>
                <h1 class="mt-3 font-heading text-4xl font-bold sm:text-5xl">{{ $plant->common_name }}</h1>
                <p class="mt-1 font-accent text-xl italic text-forest sm:mt-2 sm:text-2xl">{{ $plant->scientific_name }}</p>
                @if ($plant->common_names)
                    <p class="mt-3 text-gray-700"><span class="font-medium">Also known as:</span> {{ $plant->common_names }}</p>
                @endif

                <div class="mt-6 space-y-4 lg:hidden">
                    @include('plants._media-actions')
                </div>

                <dl class="mt-8 grid gap-3 sm:mt-10 sm:grid-cols-2 sm:gap-6">
                    @foreach (array_filter([
                        'Habitat' => $plant->habitat,
                        'Distribution' => $plant->distribution,
                        'Plant parts studied' => $plant->plant_parts,
                        'Physical features' => $plant->physical_features,
                        'Growing conditions' => $plant->care_requirements,
                        'USDA hardiness zones' => $plant->hardiness_zones,
                        'Documented extract types' => $plant->extract_types,
                        'Research reference' => $plant->reference,
                    ]) as $label => $value)
                        <div class="card bg-sand">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-forest">{{ $label }}</dt>
                            <dd class="mt-2 text-gray-800">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div @class([
                    'card mt-6 flex gap-4',
                    'bg-red-50/80 ring-red-200' => $plant->known_toxins,
                    'bg-sand' => ! $plant->known_toxins,
                ])>
                    <span @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                        'bg-red-100 text-red-700' => $plant->known_toxins,
                        'bg-leaf/30 text-forest' => ! $plant->known_toxins,
                    ]) aria-hidden="true">
                        @if ($plant->known_toxins)
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3z"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <h2 @class([
                            'text-xs font-semibold uppercase tracking-wider',
                            'text-red-800' => $plant->known_toxins,
                            'text-forest' => ! $plant->known_toxins,
                        ])>Known hazards and toxins</h2>
                        @if ($plant->known_toxins)
                            <p class="mt-2 text-gray-800">{{ $plant->known_toxins }}</p>
                        @else
                            <p class="mt-2 text-gray-800">None recorded in the reviewed sources.</p>
                            <p class="mt-1 text-sm text-gray-600">Absence of a record is not evidence of safety.</p>
                        @endif
                    </div>
                </div>

                @if ($plant->medicinalActions->isNotEmpty())
                    <div class="card mt-6">
                        <h2 class="font-heading text-lg font-semibold">Reported traditional uses</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Actions attributed to this plant in traditional and ethnobotanical sources. They are not clinically verified and are not equivalent to laboratory or human-study evidence.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($plant->medicinalActions->sortBy('name') as $action)
                                <a href="{{ route('plants.index', ['action' => $action->slug]) }}" class="rounded-full bg-sand px-3 py-1.5 text-sm text-forest-dark ring-1 ring-forest/15 hover:bg-leaf/30 sm:py-1">{{ $action->name }}</a>
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-gray-600">Evidence level: traditional use &middot; Source: {{ $plant->medicinalActions->first()->pivot->source_provider ?? 'curated record' }}</p>
                    </div>
                @endif

                <div class="mt-6 text-sm text-gray-600">
                    <p>
                        Source: <span class="font-medium text-gray-800">{{ $plant->source_label }}</span>
                        @if ($plant->source_provider)
                            &middot;
                            @if ($plant->source_url)
                                <a href="{{ $plant->source_url }}" target="_blank" rel="noopener" class="font-medium text-forest hover:underline">{{ $plant->source_provider }}</a>
                            @else
                                {{ $plant->source_provider }}
                            @endif
                            @if ($plant->source_retrieved_at)
                                (retrieved {{ $plant->source_retrieved_at->format('M Y') }})
                            @endif
                        @endif
                        &middot; Last updated {{ $plant->updated_at->format('M j, Y') }}
                    </p>
                </div>
                <p class="mt-4 rounded-lg bg-sand px-4 py-3 text-sm text-gray-700 ring-1 ring-forest/10">
                    This profile supports research only. It is not medical advice and does not recommend dosage, preparation or consumption.
                </p>
            </div>

            <aside class="space-y-6">
                <div class="hidden space-y-6 lg:block">
                    @include('plants._media-actions')
                </div>

                <div class="card p-0">
                    <div class="flex items-center justify-between px-6 pt-5">
                        <h2 class="font-heading text-lg font-semibold">Occurrence map</h2>
                        <a href="{{ route('map', ['plant' => $plant->id]) }}" class="text-xs font-medium text-forest hover:underline">Full map</a>
                    </div>
                    @if ($mapPoints->isEmpty())
                        <p class="px-6 pb-6 pt-3 text-sm text-gray-600">No mapped sightings yet.</p>
                    @else
                        <div class="isolate mt-3 h-56 overflow-hidden rounded-b-xl" x-data x-init="sightingMap($el, { points: @js($mapPoints->map(fn ($point) => $point + ['common_name' => $plant->common_name])) })"></div>
                    @endif
                </div>

                <div class="card">
                    <h2 class="font-heading text-lg font-semibold">Trusted sightings</h2>
                    @forelse ($sightings as $record)
                        <div class="mt-3 border-t border-forest/10 pt-3 text-sm">
                            <p class="font-medium">{{ $record['location']['precision'] === 'exact' ? ($record['location']['location_name'] ?? 'Pinned location') : 'Generalized area (protected record)' }}</p>
                            <p class="text-gray-600">{{ $record['sighting']->observed_on->format('M j, Y') }} &middot; {{ $record['sighting']->plant_part }}</p>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-gray-600">No reviewed sightings yet.</p>
                    @endforelse
                </div>

                @canany(['update', 'delete'], $plant)
                    <div class="flex flex-wrap gap-3">
                        @can('update', $plant)
                            <a href="{{ route('plants.edit', $plant) }}" class="btn-secondary">Edit profile</a>
                        @endcan
                        @can('delete', $plant)
                            <form method="POST" action="{{ route('plants.destroy', $plant) }}" onsubmit="return confirm('Delete this plant profile and all of its sightings?')">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-medium text-red-700 ring-1 ring-red-700/30 hover:bg-red-50">Delete</button>
                            </form>
                        @endcan
                    </div>
                @endcanany
            </aside>
        </div>
    </section>
</x-site-layout>
