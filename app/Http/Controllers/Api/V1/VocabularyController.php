<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MedicinalAction;
use App\Models\Plant;
use App\Models\Sighting;
use Illuminate\Http\JsonResponse;

/**
 * Controlled vocabularies the client needs to build filters and forms.
 */
class VocabularyController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'categories' => Plant::CATEGORIES,
            'plant_parts' => Plant::PLANT_PARTS,
            'source_labels' => Plant::SOURCE_LABELS,
            'conservation_statuses' => Plant::CONSERVATION_STATUSES,
            'families' => Plant::whereNotNull('family')->distinct()->orderBy('family')->pluck('family'),
            'traditional_uses' => MedicinalAction::orderBy('name')->get(['name', 'slug']),
            'sighting_visibilities' => Sighting::VISIBILITIES,
            'sighting_statuses' => Sighting::STATUSES,
            'review_decisions' => Sighting::REVIEW_DECISIONS,
        ]);
    }
}
