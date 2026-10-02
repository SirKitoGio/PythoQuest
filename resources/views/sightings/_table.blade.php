@if ($sightings->isEmpty())
    <p class="py-10 text-center text-gray-700">
        No sightings yet. <a href="{{ route('sightings.create') }}" class="font-semibold text-forest hover:underline">Log your first one.</a>
    </p>
@else
    <div class="mt-4 overflow-x-auto">
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
