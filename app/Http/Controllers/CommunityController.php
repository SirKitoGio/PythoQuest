<?php

namespace App\Http\Controllers;

use App\Models\Sighting;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Other researchers' open records that the community can independently confirm.
 */
class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $sightings = Sighting::query()
            ->with(['plant', 'user'])
            ->withCount('confirmations')
            ->withExists(['confirmations as confirmed_by_me' => fn ($query) => $query->where('user_id', $user->id)])
            ->whereIn('status', ['pending', 'needs correction', 'trusted'])
            ->where('visibility', '!=', 'private')
            ->where('user_id', '!=', $user->id)
            ->latest()
            ->paginate(12);

        return view('community.index', compact('sightings'));
    }
}
