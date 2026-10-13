@if ($sightings->isEmpty())
    <p class="py-10 text-center text-gray-700">
        No sightings yet. <a href="{{ route('sightings.create') }}" class="font-semibold text-forest hover:underline">Log your first one.</a>
    </p>
@else
    {{-- Phones: tappable cards so status is always visible without sideways scrolling. --}}
    <ul class="mt-4 divide-y divide-forest/10 sm:hidden">
        @foreach ($sightings as $sighting)
            <li>
                <a href="{{ route('sightings.show', $sighting) }}" class="flex items-center gap-3 py-3 active:bg-sand/60">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ $sighting->plant->common_name }}</p>
                        <p class="truncate text-sm text-gray-600">
                            {{ $sighting->observed_on->format('M j') }} &middot; {{ ucfirst($sighting->plant_part) }}
                            @if ($sighting->location_name) &middot; {{ $sighting->location_name }} @endif
                        </p>
                    </div>
                    <x-status-badge :status="$sighting->status" />
                    <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="mt-4 hidden overflow-x-auto sm:block">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-forest/20 text-xs uppercase tracking-wider text-gray-600">
                <tr>
                    <th class="py-3 pe-4">Plant</th>
                    <th class="py-3 pe-4">Observed</th>
                    <th class="py-3 pe-4">Part</th>
                    <th class="py-3 pe-4">Location</th>
                    <th class="py-3 pe-4">Status</th>
                    <th class="py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-forest/10">
                @foreach ($sightings as $sighting)
                    <tr>
                        <td class="py-3 pe-4 font-medium">{{ $sighting->plant->common_name }}</td>
                        <td class="py-3 pe-4">{{ $sighting->observed_on->format('M j, Y') }}</td>
                        <td class="py-3 pe-4 capitalize">{{ $sighting->plant_part }}</td>
                        <td class="py-3 pe-4">{{ $sighting->location_name ?? '—' }}</td>
                        <td class="py-3 pe-4"><x-status-badge :status="$sighting->status" /></td>
                        <td class="py-3 text-right"><a href="{{ route('sightings.show', $sighting) }}" class="font-medium text-forest hover:underline">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
