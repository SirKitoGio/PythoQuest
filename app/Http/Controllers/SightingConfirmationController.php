<?php

namespace App\Http\Controllers;

use App\Models\Sighting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Community confirmations add evidence but never change a record's review status (FR-11).
 */
class SightingConfirmationController extends Controller
{
    public function store(Request $request, Sighting $sighting): RedirectResponse
    {
        Gate::authorize('confirm', $sighting);

        $request->validate(['comment' => ['nullable', 'string', 'max:255']]);

        // The unique (sighting_id, user_id) index makes concurrent double-submits a no-op.
        $sighting->confirmations()->insertOrIgnore([
            'sighting_id' => $sighting->id,
            'user_id' => $request->user()->id,
            'comment' => $request->input('comment'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Thanks — your confirmation was added as supporting evidence.');
    }

    public function destroy(Request $request, Sighting $sighting): RedirectResponse
    {
        $sighting->confirmations()->where('user_id', $request->user()->id)->delete();

        return back()->with('status', 'Confirmation withdrawn.');
    }
}
