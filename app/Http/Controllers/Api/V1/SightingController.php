<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SubmitSighting;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSightingRequest;
use App\Http\Resources\SightingResource;
use App\Models\Sighting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SightingController extends Controller
{
    /**
     * The authenticated user's own submissions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SightingResource::collection(
            $request->user()->sightings()->with('plant')->withCount('confirmations')->latest()->paginate(20)
        );
    }

    public function store(StoreSightingRequest $request, SubmitSighting $submit): JsonResponse
    {
        $sighting = $submit->create($request->user(), $request->safe()->except('photo'), $request->file('photo'));

        return (new SightingResource($sighting->load('plant')))->response()->setStatusCode(201);
    }

    public function show(Sighting $sighting): SightingResource
    {
        Gate::authorize('view', $sighting);

        return new SightingResource($sighting->load('plant')->loadCount('confirmations'));
    }

    public function update(StoreSightingRequest $request, Sighting $sighting, SubmitSighting $submit): SightingResource
    {
        Gate::authorize('update', $sighting);

        $submit->update($sighting, $request->safe()->except(['photo', 'identification_id']), $request->file('photo'));

        return new SightingResource($sighting->load('plant'));
    }

    public function destroy(Sighting $sighting): Response
    {
        Gate::authorize('delete', $sighting);

        if ($sighting->photo_path) {
            Storage::disk(config('filesystems.media'))->delete($sighting->photo_path);
        }

        $sighting->delete();

        return response()->noContent();
    }
}
