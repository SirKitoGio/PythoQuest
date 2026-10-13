<?php

namespace App\Http\Resources;

use App\Models\Identification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Identification
 */
class IdentificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'plant_part' => $this->plant_part,
            'image_url' => $this->imageUrl(),
            'model_version' => $this->model_version,
            'candidates' => $this->candidates ?? [],
            'top_confidence' => $this->top_confidence,
            'needs_more_images' => $this->needs_more_images,
            'escalated_at' => $this->escalated_at?->toIso8601String(),
            'confirmed_plant' => new PlantResource($this->whenLoaded('confirmedPlant')),
            'error' => $this->error,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
