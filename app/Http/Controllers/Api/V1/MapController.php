<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sighting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sighting points as GeoJSON, with precision reduced per viewer and record class (Section IX.C).
 */
class MapController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bbox' => ['nullable', 'regex:/^-?\d+(\.\d+)?(,-?\d+(\.\d+)?){3}$/'],
            'plant_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:trusted'],
        ]);
        $viewer = $request->user();

        $sightings = Sighting::query()
            ->with('plant:id,common_name,scientific_name,conservation_status')
            ->whereIn('status', Sighting::MAPPABLE_STATUSES)
            ->when($validated['plant_id'] ?? null, fn ($query, $plantId) => $query->where('plant_id', $plantId))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['bbox'] ?? null, function ($query, string $bbox) {
                [$west, $south, $east, $north] = array_map('floatval', explode(',', $bbox));
                $query->whereBetween('latitude', [$south, $north])->whereBetween('longitude', [$west, $east]);
            })
            ->latest('observed_on')
            ->limit(2000)
            ->get();

        $features = $sightings
            ->map(fn (Sighting $sighting) => [$sighting, $sighting->locationFor($viewer)])
            ->filter(fn (array $pair) => $pair[1] !== null)
            ->map(fn (array $pair) => [
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [$pair[1]['longitude'], $pair[1]['latitude']]],
                'properties' => [
                    'plant_id' => $pair[0]->plant_id,
                    'common_name' => $pair[0]->plant->common_name,
                    'scientific_name' => $pair[0]->plant->scientific_name,
                    'status' => $pair[0]->status,
                    'precision' => $pair[1]['precision'],
                    'location_name' => $pair[1]['location_name'],
                    'observed_on' => $pair[0]->observed_on->toDateString(),
                ],
            ])
            ->values();

        return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
    }
}
