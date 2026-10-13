<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlantResource;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return PlantResource::collection($request->user()->favoritePlants()->latest('favorites.created_at')->paginate(50));
    }

    public function store(Request $request, Plant $plant): Response
    {
        $request->user()->favoritePlants()->syncWithoutDetaching([$plant->id]);

        return response()->noContent();
    }

    public function destroy(Request $request, Plant $plant): Response
    {
        $request->user()->favoritePlants()->detach($plant->id);

        return response()->noContent();
    }
}
