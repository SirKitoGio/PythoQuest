<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ReviewSighting;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSightingRequest;
use App\Http\Resources\SightingResource;
use App\Models\Sighting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * Pending submissions awaiting an expert, oldest first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SightingResource::collection(
            Sighting::with('plant')
                ->withCount('confirmations')
                ->where('status', 'pending')
                ->where('user_id', '!=', $request->user()->id)
                ->oldest()
                ->paginate(20)
        );
    }

    public function store(ReviewSightingRequest $request, Sighting $sighting, ReviewSighting $review): SightingResource
    {
        $reviewed = $review->handle($sighting, $request->user(), $request->validated('decision'), $request->validated('note'));

        return new SightingResource($reviewed->load('plant')->loadCount('confirmations'));
    }
}
