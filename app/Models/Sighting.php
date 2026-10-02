<?php

namespace App\Models;

use Database\Factories\SightingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'plant_id', 'latitude', 'longitude', 'observed_on', 'plant_part',
    'visibility', 'status', 'location_name', 'photo_path', 'notes',
])]
class Sighting extends Model
{
    /** @use HasFactory<SightingFactory> */
    use HasFactory;

    public const VISIBILITIES = ['public', 'private', 'protected'];

    public const STATUSES = ['pending', 'needs correction', 'trusted', 'rejected'];

    protected function casts(): array
    {
        return [
            'observed_on' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
