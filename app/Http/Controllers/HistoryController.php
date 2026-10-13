<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A researcher's retained activity: searches, scans, submissions, confirmations and favorites (FR-12).
 */
class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('history.index', [
            'favorites' => $user->favoritePlants()->latest('favorites.created_at')->get(),
            'searches' => $user->searchHistories()->latest()->take(20)->get(),
            'identifications' => $user->identifications()->with('topPlant')->latest()->take(12)->get(),
            'sightings' => $user->sightings()->with('plant')->latest()->take(10)->get(),
            'confirmations' => $user->confirmations()->with('sighting.plant')->latest()->take(10)->get(),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->searchHistories()->delete();

        return back()->with('status', 'Search history cleared.');
    }
}
