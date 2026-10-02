<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlantRequest;
use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->string('q')->trim()->toString();
        $category = $request->string('category')->toString();

        $plants = Plant::query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('common_name', 'like', "%{$search}%")
                    ->orWhere('scientific_name', 'like', "%{$search}%");
            }))
            ->when(in_array($category, Plant::CATEGORIES, true), fn ($query) => $query->where('category', $category))
            ->orderBy('common_name')
            ->paginate(9)
            ->withQueryString();

        return view('plants.index', [
            'plants' => $plants,
            'search' => $search,
            'category' => $category,
            'categories' => Plant::CATEGORIES,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('plants.create', ['plant' => new Plant]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlantRequest $request)
    {
        $plant = Plant::create($request->validated());

        return redirect()->route('plants.show', $plant)->with('status', 'Plant profile created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Plant $plant)
    {
        $sightings = $plant->sightings()
            ->with('user')
            ->where('status', 'trusted')
            ->latest('observed_on')
            ->take(5)
            ->get();

        return view('plants.show', compact('plant', 'sightings'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Plant $plant)
    {
        return view('plants.edit', compact('plant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StorePlantRequest $request, Plant $plant)
    {
        $plant->update($request->validated());

        return redirect()->route('plants.show', $plant)->with('status', 'Plant profile updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plant $plant)
    {
        $plant->delete();

        return redirect()->route('plants.index')->with('status', 'Plant profile deleted.');
    }
}
