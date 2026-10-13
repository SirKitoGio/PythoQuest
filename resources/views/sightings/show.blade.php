<x-app-layout>
    <x-slot name="header">
        <a href="{{ $sighting->user_id === Auth::id() ? route('sightings.index') : route('review.index') }}" class="text-sm font-medium text-forest hover:underline">&larr; {{ $sighting->user_id === Auth::id() ? 'My sightings' : 'Review queue' }}</a>
        <div class="mt-3 flex flex-wrap items-center gap-4">
            <h2 class="font-heading text-3xl font-bold sm:text-4xl">{{ $sighting->plant->common_name }}</h2>
            <x-status-badge :status="$sighting->status" />
        </div>
        <p class="mt-1 font-accent text-xl italic text-forest">{{ $sighting->plant->scientific_name }}</p>
    </x-slot>

    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[380px_1fr]">
        @if ($sighting->reviewed_at)
            <div @class([
                'rounded-xl px-5 py-4 text-sm lg:col-span-2',
                'bg-leaf/25 text-forest-dark' => $sighting->status === 'trusted',
                'bg-orange-100 text-orange-900' => $sighting->status === 'needs correction',
                'bg-red-100 text-red-900' => $sighting->status === 'rejected',
                'bg-purple-100 text-purple-900' => in_array($sighting->status, ['sensitive-restricted', 'pending']),
            ])>
                <p class="font-semibold">
                    {{ $sighting->status === 'pending' ? 'Resubmitted — previous review' : 'Reviewed' }}
                    by {{ $sighting->reviewer?->name ?? 'an expert' }} {{ $sighting->reviewed_at->diffForHumans() }}
                </p>
                @if ($sighting->review_note)
                    <p class="mt-1">{{ $sighting->review_note }}</p>
                @endif
                @if ($sighting->status === 'needs correction' && $sighting->user_id === Auth::id())
                    <a href="{{ route('sightings.edit', $sighting) }}" class="mt-2 inline-block font-semibold underline">Correct and resubmit</a>
                @endif
            </div>
        @endif

        <x-plant-image :plant="$sighting->plant" :src="$sighting->photoUrl()" class="h-80 rounded-xl shadow-sm" />

        <div class="card bg-sand">
            <dl class="grid gap-6 sm:grid-cols-2">
                @foreach ([
                    'Observed on' => $sighting->observed_on->format('F j, Y'),
                    'Plant part' => ucfirst($sighting->plant_part),
                    'Location' => $sighting->location_name ?? 'Pinned location',
                    'Coordinates' => number_format($sighting->latitude, 5).', '.number_format($sighting->longitude, 5),
                    'Location status' => ucfirst($sighting->visibility),
                    'Submitted' => $sighting->created_at->diffForHumans(),
                    'Community confirmations' => $sighting->confirmations->count(),
                    'Model match' => $sighting->identification?->top_confidence !== null
                        ? number_format($sighting->identification->top_confidence * 100).'% ('.$sighting->identification->model_version.')'
                        : 'No scan linked',
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
                @can('update', $sighting)
                    <a href="{{ route('sightings.edit', $sighting) }}" class="btn-secondary">Edit</a>
                @endcan
                @can('delete', $sighting)
                    <form method="POST" action="{{ route('sightings.destroy', $sighting) }}" onsubmit="return confirm('Delete this sighting?')">
                        @csrf
                        @method('DELETE')
                        <button class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-medium text-red-700 ring-1 ring-red-700/30 hover:bg-red-50">Delete</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
