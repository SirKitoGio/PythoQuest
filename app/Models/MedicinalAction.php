<?php

namespace App\Models;

use Database\Factories\MedicinalActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Controlled vocabulary of documented traditional medicinal actions (e.g. "Antibacterial").
 * These describe what sources report, never a recommendation.
 */
#[Fillable(['name', 'slug'])]
class MedicinalAction extends Model
{
    /** @use HasFactory<MedicinalActionFactory> */
    use HasFactory;

    public function plants(): BelongsToMany
    {
        return $this->belongsToMany(Plant::class)->withPivot(['evidence_level', 'source_provider']);
    }
}
