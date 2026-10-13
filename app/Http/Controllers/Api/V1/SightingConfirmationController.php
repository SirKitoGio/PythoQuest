<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sighting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SightingConfirmationController extends Controller
{
    public function store(Request $request, Sighting $sighting): JsonResponse
    {
        Gate::authorize('confirm', $sighting);

        $request->validate(['comment' => ['nullable', 'string', 'max:255']]);

        $sighting->confirmations()->insertOrIgnore([
            'sighting_id' => $sighting->id,
            'user_id' => $request->user()->id,
            'comment' => $request->input('comment'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['confirmations_count' => $sighting->confirmations()->count(), 'status' => $sighting->status], 201);
    }

    public function destroy(Request $request, Sighting $sighting): JsonResponse
    {
        $sighting->confirmations()->where('user_id', $request->user()->id)->delete();

        return response()->json(['confirmations_count' => $sighting->confirmations()->count()]);
    }
}
