<?php

namespace App\Models;

use Database\Factories\IdentificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * One image-identification request ("scan") and the model's ranked candidates.
 * A prediction is never treated as a confirmed taxon.
 */
#[Fillable([
    'image_path', 'plant_part', 'status', 'model_version', 'candidates', 'top_plant_id',
    'top_confidence', 'needs_more_images', 'latency_ms', 'error', 'escalated_at',
])]
class Identification extends Model
{
    /** @use HasFactory<IdentificationFactory> */
    use HasFactory;

    public const STATUSES = ['queued', 'processing', 'completed', 'failed'];

    protected function casts(): array
    {
        return [
            'candidates' => 'array',
            'top_confidence' => 'float',
            'needs_more_images' => 'boolean',
            'escalated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topPlant(): BelongsTo
    {
        return $this->belongsTo(Plant::class, 'top_plant_id');
    }

    public function confirmedPlant(): BelongsTo
    {
        return $this->belongsTo(Plant::class, 'confirmed_plant_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function sightings(): HasMany
    {
        return $this->hasMany(Sighting::class);
    }

    public function imageUrl(): string
    {
        return Storage::disk(config('filesystems.media'))->url($this->image_path);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }
}
