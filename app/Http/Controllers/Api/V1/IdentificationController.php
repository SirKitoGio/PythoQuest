<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\EscalateIdentification;
use App\Actions\StartIdentification;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIdentificationRequest;
use App\Http\Resources\IdentificationResource;
use App\Models\Identification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class IdentificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return IdentificationResource::collection($request->user()->identifications()->latest()->paginate(20));
    }

    /**
     * Accepts the image and returns 202: results arrive on the user's private channel
     * as "identification.updated", or by polling the show endpoint.
     */
    public function store(StoreIdentificationRequest $request, StartIdentification $start): JsonResponse
    {
        $identification = $start->handle($request->user(), $request->file('image'), $request->validated('plant_part'));

        return (new IdentificationResource($identification->refresh()))->response()->setStatusCode(202);
    }

    public function show(Identification $identification): IdentificationResource
    {
        Gate::authorize('view', $identification);

        return new IdentificationResource($identification->load('confirmedPlant'));
    }

    public function escalate(Identification $identification, EscalateIdentification $escalate): IdentificationResource
    {
        Gate::authorize('escalate', $identification);

        return new IdentificationResource($escalate->handle($identification));
    }
}
