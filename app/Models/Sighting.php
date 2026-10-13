<?php

namespace App\Models;

use Database\Factories\SightingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'plant_id', 'identification_id', 'latitude', 'longitude', 'observed_on', 'plant_part',
    'visibility', 'status', 'location_name', 'photo_path', 'notes',
])]
class Sighting extends Model
{
    /** @use HasFactory<SightingFactory> */
    use HasFactory;

    public const VISIBILITIES = ['public', 'private', 'protected'];

    public const STATUSES = ['pending', 'needs correction', 'trusted', 'rejected', 'sensitive-restricted'];

    /**
     * Outcomes an expert reviewer may record.
     */
    public const REVIEW_DECISIONS = ['trusted', 'needs correction', 'rejected', 'sensitive-restricted'];

    /**
     * Statuses that may appear on the public map (always subject to locationFor()).
     */
    public const MAPPABLE_STATUSES = ['pending', 'needs correction', 'trusted', 'sensitive-restricted'];

    /**
     * Size of the generalization grid in degrees (~1.1 km at the equator), following
     * GBIF guidance on generalizing sensitive occurrence data.
     */
    public const GENERALIZATION_GRID = 0.01;

    protected function casts(): array
    {
        return [
            'observed_on' => 'date',
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function identification(): BelongsTo
    {
        return $this->belongsTo(Identification::class);
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(SightingConfirmation::class);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk(config('filesystems.media'))->url($this->photo_path) : null;
    }

    /**
     * The location precision a viewer is allowed to see.
     *
     * Owners and reviewers see exact coordinates. Everyone else sees an exact pin only for
     * trusted, public records of non-sensitive plants; protected, unverified and sensitive
     * records are snapped to the centre of a grid cell; private and rejected records are hidden.
     *
     * @return array{latitude: float, longitude: float, precision: 'exact'|'generalized', location_name: ?string}|null
     */
    public function locationFor(?User $viewer): ?array
    {
        if ($viewer && ($viewer->id === $this->user_id || $viewer->isReviewer())) {
            return $this->exactLocation();
        }

        if ($this->visibility === 'private' || ! in_array($this->status, self::MAPPABLE_STATUSES, true)) {
            return null;
        }

        if ($this->status === 'trusted' && $this->visibility === 'public' && ! $this->plant->isSensitive()) {
            return $this->exactLocation();
        }

        $grid = self::GENERALIZATION_GRID;

        return [
            'latitude' => round(floor($this->latitude / $grid) * $grid + $grid / 2, 4),
            'longitude' => round(floor($this->longitude / $grid) * $grid + $grid / 2, 4),
            'precision' => 'generalized',
            'location_name' => null,
        ];
    }

    /**
     * @return array{latitude: float, longitude: float, precision: 'exact', location_name: ?string}
     */
    private function exactLocation(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'precision' => 'exact',
            'location_name' => $this->location_name,
        ];
    }
}
