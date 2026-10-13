<?php

namespace App\Http\Controllers;

use App\Actions\SubmitSighting;
use App\Http\Requests\StoreSightingRequest;
use App\Models\Plant;
use App\Models\Sighting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SightingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $sightings = $request->user()->sightings()
            ->with('plant')
            ->withCount('confirmations')
            ->latest()
            ->paginate(10);

        return view('sightings.index', compact('sightings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $identification = $request->integer('identification')
            ? $request->user()->identifications()->find($request->integer('identification'))
            : null;

        return view('sightings.create', [
            'sighting' => new Sighting([
                'plant_id' => $request->integer('plant') ?: $identification?->top_plant_id,
                'identification_id' => $identification?->id,
                'plant_part' => $identification?->plant_part,
                'observed_on' => now(),
                'visibility' => 'public',
                'photo_path' => $identification?->image_path,
            ]),
            'plants' => $this->plantOptions($request->integer('plant') ?: $identification?->top_plant_id),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSightingRequest $request, SubmitSighting $submit): RedirectResponse
    {
        $sighting = $submit->create($request->user(), $request->safe()->except('photo'), $request->file('photo'));

        return redirect()->route('sightings.show', $sighting)
            ->with('status', 'Sighting submitted. It stays pending until an expert reviews it.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sighting $sighting): View
    {
        Gate::authorize('view', $sighting);

        $sighting->load(['plant', 'reviewer', 'identification', 'confirmations.user']);

        return view('sightings.show', compact('sighting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sighting $sighting): View
    {
        Gate::authorize('update', $sighting);

        return view('sightings.edit', [
            'sighting' => $sighting,
            'plants' => $this->plantOptions($sighting->plant_id),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSightingRequest $request, Sighting $sighting, SubmitSighting $submit): RedirectResponse
    {
        Gate::authorize('update', $sighting);

        $submit->update($sighting, $request->safe()->except(['photo', 'identification_id']), $request->file('photo'));

        return redirect()->route('sightings.show', $sighting)->with('status', 'Sighting updated and sent back for review.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sighting $sighting): RedirectResponse
    {
        Gate::authorize('delete', $sighting);

        if ($sighting->photo_path) {
            Storage::disk(config('filesystems.media'))->delete($sighting->photo_path);
        }

        $sighting->delete();

        return redirect()->route('sightings.index')->with('status', 'Sighting deleted.');
    }

    /**
     * Field records concentrate on the curated pilot species (those with habitat notes) and
     * species already observed; the rest of the catalogue is reachable through the search box.
     *
     * @return Collection<int, Plant>
     */
    private function plantOptions(?int $selectedId): Collection
    {
        return Plant::query()
            ->whereNotNull('habitat')
            ->orWhereHas('sightings')
            ->when($selectedId, fn ($query) => $query->orWhereKey($selectedId))
            ->orderBy('common_name')
            ->get(['id', 'common_name', 'scientific_name']);
    }
}
