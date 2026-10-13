<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * The public sighting map. Points are loaded from the precision-safe API endpoint.
     */
    public function index(): View
    {
        return view('map', [
            'plants' => Plant::whereHas('sightings')->orderBy('common_name')->get(['id', 'common_name']),
        ]);
    }
}
