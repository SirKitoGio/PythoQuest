<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlantResource;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlantController extends Controller
{
    /**
     * Search and filter the catalogue (FR-01, FR-02).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('q')->trim()->toString();
        $filters = array_filter($request->only(['category', 'family', 'part', 'action', 'toxins', 'conservation']), 'is_string');
        $user = $request->user();

        $plants = Plant::query()
            ->search($search)
            ->filter($filters)
            ->withCount(['sightings' => fn ($query) => $query->where('status', 'trusted')])
            ->when($user, fn ($query) => $query->withExists(['favoritedBy as is_favorite' => fn ($query) => $query->whereKey($user->id)]))
            ->when($search === '', fn ($query) => $query->orderByDesc('sightings_count')->orderBy('common_name'))
            ->paginate(min($request->integer('per_page', 20), 100))
            ->withQueryString();

        if ($user && ($search !== '' || $filters) && $plants->onFirstPage() && ! $request->boolean('suggest')) {
            $user->searchHistories()->create([
                'query' => $search ?: null,
                'filters' => $filters ?: null,
                'results_count' => $plants->total(),
            ]);
        }

        return PlantResource::collection($plants);
    }

    public function show(Plant $plant): PlantResource
    {
        return new PlantResource($plant->load('medicinalActions')->loadCount(['sightings' => fn ($query) => $query->where('status', 'trusted')]));
    }
}
