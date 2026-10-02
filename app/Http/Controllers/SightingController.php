<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSightingRequest;
use App\Models\Plant;
use App\Models\Sighting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SightingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sightings = $request->user()->sightings()
            ->with('plant')
            ->latest()
            ->paginate(10);

        return view('sightings.index', compact('sightings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return view('sightings.create', [
            'sighting' => new Sighting([
                'plant_id' => $request->integer('plant') ?: null,
                'observed_on' => now(),
                'visibility' => 'public',
            ]),
            'plants' => Plant::orderBy('common_name')->get(['id', 'common_name', 'scientific_name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSightingRequest $request)
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('sightings', 'public');
        }

        $sighting = $request->user()->sightings()->create($data + ['status' => 'pending']);

        return redirect()->route('sightings.show', $sighting)
            ->with('status', 'Sighting submitted. It stays pending until an expert reviews it.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Sighting $sighting)
    {
        $this->authorizeOwner($request, $sighting);

        return view('sightings.show', compact('sighting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Sighting $sighting)
    {
        $this->authorizeOwner($request, $sighting);

        return view('sightings.edit', [
            'sighting' => $sighting,
            'plants' => Plant::orderBy('common_name')->get(['id', 'common_name', 'scientific_name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSightingRequest $request, Sighting $sighting)
    {
        $this->authorizeOwner($request, $sighting);

        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            if ($sighting->photo_path) {
                Storage::disk('public')->delete($sighting->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('sightings', 'public');
        }

        $sighting->update($data + ['status' => 'pending']);

        return redirect()->route('sightings.show', $sighting)->with('status', 'Sighting updated and sent back for review.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Sighting $sighting)
    {
        $this->authorizeOwner($request, $sighting);

        if ($sighting->photo_path) {
            Storage::disk('public')->delete($sighting->photo_path);
        }

        $sighting->delete();

        return redirect()->route('sightings.index')->with('status', 'Sighting deleted.');
    }

    private function authorizeOwner(Request $request, Sighting $sighting): void
    {
        abort_unless($sighting->user_id === $request->user()->id, 403);
    }
}
