<?php

namespace App\Http\Resources;

use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Plant
 */
class PlantResource extends JsonResource
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
            'common_name' => $this->common_name,
            'scientific_name' => $this->scientific_name,
            'family' => $this->family,
            'common_names' => $this->common_names ? explode(', ', $this->common_names) : [],
            'category' => $this->category,
            'image_url' => $this->photoUrl(),
            'conservation' => [
                'code' => $this->conservation_status,
                'label' => Plant::CONSERVATION_STATUSES[$this->conservation_status] ?? null,
                'is_sensitive' => $this->isSensitive(),
            ],
            'has_documented_toxins' => $this->known_toxins !== null,
            'trusted_sightings_count' => $this->whenCounted('sightings'),
            'is_favorite' => $this->when(isset($this->is_favorite), fn () => (bool) $this->is_favorite),
            $this->mergeWhen($request->routeIs('api.v1.plants.show'), fn () => [
                'habitat' => $this->habitat,
                'distribution' => $this->distribution,
                'native_range' => $this->native_range,
                'hardiness_zones' => $this->hardiness_zones,
                'care_requirements' => $this->care_requirements,
                'plant_parts' => $this->plant_parts ? array_map('trim', explode(',', $this->plant_parts)) : [],
                'physical_features' => $this->physical_features,
                'known_toxins' => $this->known_toxins,
                'extract_types' => $this->extract_types,
                'traditional_uses' => $this->whenLoaded('medicinalActions', fn () => $this->medicinalActions->map(fn ($action) => [
                    'name' => $action->name,
                    'slug' => $action->slug,
                    'evidence_level' => $action->pivot->evidence_level,
                    'source' => $action->pivot->source_provider,
                ])),
                'source' => [
                    'label' => $this->source_label,
                    'provider' => $this->source_provider,
                    'url' => $this->source_url,
                    'retrieved_at' => $this->source_retrieved_at?->toIso8601String(),
                    'reference' => $this->reference,
                ],
                'disclaimer' => 'Research information only. Not medical advice; no dosage, preparation or consumption guidance.',
            ]),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
