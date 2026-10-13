<?php

namespace App\Http\Controllers;

use App\Models\Identification;
use App\Models\Sighting;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The expert review queue: pending sightings and escalated identifications.
 */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString() === 'identifications' ? 'identifications' : 'sightings';

        return view('review.index', [
            'tab' => $tab,
            'sightings' => Sighting::query()
                ->with(['plant', 'user', 'identification'])
                ->withCount('confirmations')
                ->where('status', 'pending')
                ->where('user_id', '!=', $request->user()->id)
                ->oldest()
                ->paginate(10, pageName: 'sightings_page'),
            'identifications' => Identification::query()
                ->with(['user', 'topPlant'])
                ->whereNotNull('escalated_at')
                ->whereNull('reviewed_at')
                ->where('user_id', '!=', $request->user()->id)
                ->oldest('escalated_at')
                ->paginate(10, pageName: 'identifications_page'),
            'counts' => [
                'sightings' => Sighting::where('status', 'pending')->where('user_id', '!=', $request->user()->id)->count(),
                'identifications' => Identification::whereNotNull('escalated_at')->whereNull('reviewed_at')->where('user_id', '!=', $request->user()->id)->count(),
            ],
        ]);
    }
}
