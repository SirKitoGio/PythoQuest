<?php

namespace App\Http\Resources;

use App\Models\Sighting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Coordinates are always passed through Sighting::locationFor() so API clients receive
 * only the precision the viewer is allowed to see.
 *
 * @mixin Sighting
 */
class SightingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $canSeeDetail = $viewer?->can('view', $this->resource) ?? false;

        return [
            'id' => $this->id,
            'plant' => new PlantResource($this->whenLoaded('plant')),
            'status' => $this->status,
            'visibility' => $this->visibility,
            'plant_part' => $this->plant_part,
            'observed_on' => $this->observed_on->toDateString(),
            'location' => $this->locationFor($viewer),
            'photo_url' => $this->photoUrl(),
            'confirmations_count' => $this->whenCounted('confirmations'),
            'is_mine' => $viewer?->id === $this->user_id,
            $this->mergeWhen($canSeeDetail, fn () => [
                'notes' => $this->notes,
                'identification_id' => $this->identification_id,
                'review' => [
                    'reviewed_at' => $this->reviewed_at?->toIso8601String(),
                    'note' => $this->review_note,
                ],
            ]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
