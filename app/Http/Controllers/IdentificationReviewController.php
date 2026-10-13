<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewIdentificationRequest;
use App\Models\AuditLog;
use App\Models\Identification;
use Illuminate\Http\RedirectResponse;

class IdentificationReviewController extends Controller
{
    /**
     * Records the expert's confirmed species for an escalated scan.
     */
    public function store(ReviewIdentificationRequest $request, Identification $identification): RedirectResponse
    {
        $identification->forceFill([
            'confirmed_plant_id' => $request->validated('plant_id'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        AuditLog::record('identification.confirmed', $identification, [
            'model_top_plant_id' => $identification->top_plant_id,
            'confirmed_plant_id' => $identification->confirmed_plant_id,
        ]);

        return back()->with('status', 'Identification confirmed.');
    }
}
