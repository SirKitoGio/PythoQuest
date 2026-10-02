<?php

namespace App\Models;

use Database\Factories\PlantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'common_name', 'scientific_name', 'category', 'habitat', 'plant_parts',
    'physical_features', 'known_toxins', 'extract_types', 'source_label',
    'reference', 'image_url',
])]
class Plant extends Model
{
    /** @use HasFactory<PlantFactory> */
    use HasFactory;

    public const CATEGORIES = ['Medicinal', 'Herbal', 'Shrubs', 'Trees'];

    public const PLANT_PARTS = ['flower', 'leaf', 'stem', 'bark', 'fruit', 'whole plant'];

    public const SOURCE_LABELS = ['trusted database', 'research paper', 'expert-reviewed submission'];

    public function sightings(): HasMany
    {
        return $this->hasMany(Sighting::class);
    }
}
