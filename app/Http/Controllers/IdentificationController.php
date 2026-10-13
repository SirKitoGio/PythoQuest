<?php

namespace App\Http\Controllers;

use App\Actions\StartIdentification;
use App\Http\Requests\StoreIdentificationRequest;
use App\Http\Resources\IdentificationResource;
use App\Models\Identification;
use App\Models\Plant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class IdentificationController extends Controller
{
    /**
     * The user's scan history.
     */
    public function index(Request $request): View
    {
        return view('identifications.index', [
            'identifications' => $request->user()->identifications()->with('topPlant')->latest()->paginate(12),
        ]);
    }

    /**
     * The scanner: capture or upload a tagged plant-part image.
     */
    public function create(): View
    {
        return view('identifications.create', ['parts' => Plant::PLANT_PARTS]);
    }

    public function store(StoreIdentificationRequest $request, StartIdentification $start): RedirectResponse
    {
        $identification = $start->handle($request->user(), $request->file('image'), $request->validated('plant_part'));

        return redirect()->route('identifications.show', $identification);
    }

    /**
     * Results page. The same URL answers JSON so the page can poll while waiting for the
     * websocket update.
     */
    public function show(Request $request, Identification $identification): View|IdentificationResource
    {
        Gate::authorize('view', $identification);

        $identification->load(['topPlant', 'confirmedPlant']);

        if ($request->wantsJson()) {
            return new IdentificationResource($identification);
        }

        return view('identifications.show', compact('identification'));
    }
}
