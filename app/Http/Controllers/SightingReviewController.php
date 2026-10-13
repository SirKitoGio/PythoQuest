<?php

namespace App\Http\Controllers;

use App\Actions\ReviewSighting;
use App\Http\Requests\ReviewSightingRequest;
use App\Models\Sighting;
use Illuminate\Http\RedirectResponse;

class SightingReviewController extends Controller
{
    public function store(ReviewSightingRequest $request, Sighting $sighting, ReviewSighting $review): RedirectResponse
    {
        $review->handle($sighting, $request->user(), $request->validated('decision'), $request->validated('note'));

        return back()->with('status', "Sighting marked {$request->validated('decision')}. The submitter has been notified.");
    }
}
