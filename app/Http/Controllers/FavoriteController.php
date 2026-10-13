<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(Request $request, Plant $plant): RedirectResponse
    {
        $request->user()->favoritePlants()->syncWithoutDetaching([$plant->id]);

        return back()->with('status', "{$plant->common_name} saved to your favorites.");
    }

    public function destroy(Request $request, Plant $plant): RedirectResponse
    {
        $request->user()->favoritePlants()->detach($plant->id);

        return back()->with('status', "{$plant->common_name} removed from your favorites.");
    }
}
