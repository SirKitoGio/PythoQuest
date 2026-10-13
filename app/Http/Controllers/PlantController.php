<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlantRequest;
use App\Models\AuditLog;
use App\Models\MedicinalAction;
use App\Models\Plant;
use App\Models\Sighting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $filters = array_filter($request->only(['category', 'family', 'part', 'action', 'toxins', 'conservation']), 'is_string');

        $plants = Plant::query()
            ->search($search)
            ->filter($filters)
            ->withCount(['sightings' => fn ($query) => $query->where('status', 'trusted')])
            ->when($search === '', fn ($query) => $query->orderByDesc('sightings_count')->orderBy('common_name'))
            ->paginate(12)
            ->withQueryString();

        if ($request->user() && ($search !== '' || $filters) && $plants->onFirstPage()) {
            $request->user()->searchHistories()->create([
                'query' => $search ?: null,
                'filters' => $filters ?: null,
                'results_count' => $plants->total(),
            ]);
        }

        return view('plants.index', [
            'plants' => $plants,
            'search' => $search,
            'filters' => $filters,
            'categories' => Plant::CATEGORIES,
            'families' => Plant::whereNotNull('family')->distinct()->orderBy('family')->pluck('family'),
            'actions' => MedicinalAction::orderBy('name')->get(['name', 'slug']),
            'favoriteIds' => $request->user()?->favoritePlants()->pluck('plants.id')->all() ?? [],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Plant::class);

        return view('plants.create', ['plant' => new Plant(['conservation_status' => 'NE'])]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlantRequest $request): RedirectResponse
    {
        $plant = Plant::create($request->validated());

        AuditLog::record('plant.created', $plant, ['scientific_name' => $plant->scientific_name]);

        return redirect()->route('plants.show', $plant)->with('status', 'Plant profile created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Plant $plant): View
    {
        $plant->load('medicinalActions');
        $viewer = $request->user();

        $records = $plant->sightings()
            ->with('plant')
            ->whereIn('status', Sighting::MAPPABLE_STATUSES)
            ->latest('observed_on')
            ->take(100)
            ->get()
            ->map(fn (Sighting $sighting) => ['sighting' => $sighting, 'location' => $sighting->locationFor($viewer)])
            ->filter(fn (array $record) => $record['location'] !== null);

        return view('plants.show', [
            'plant' => $plant,
            'sightings' => $records->where('sighting.status', 'trusted')->take(5),
            'mapPoints' => $records->map(fn (array $record) => [
                ...$record['location'],
                'status' => $record['sighting']->status,
                'observed_on' => $record['sighting']->observed_on->toDateString(),
            ])->values(),
            'isFavorite' => $viewer?->favoritePlants()->whereKey($plant->id)->exists() ?? false,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Plant $plant): View
    {
        Gate::authorize('update', $plant);

        return view('plants.edit', compact('plant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StorePlantRequest $request, Plant $plant): RedirectResponse
    {
        $plant->update($request->validated());

        AuditLog::record('plant.updated', $plant, $plant->getChanges());

        return redirect()->route('plants.show', $plant)->with('status', 'Plant profile updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plant $plant): RedirectResponse
    {
        Gate::authorize('delete', $plant);

        AuditLog::record('plant.deleted', $plant, ['scientific_name' => $plant->scientific_name]);
        $plant->delete();

        return redirect()->route('plants.index')->with('status', 'Plant profile deleted.');
    }
}
