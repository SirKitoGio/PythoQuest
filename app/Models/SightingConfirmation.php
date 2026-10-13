<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A community member's independent confirmation of a sighting. Confirmations add
 * evidence but never change a sighting's review status on their own (FR-11).
 */
#[Fillable(['sighting_id', 'user_id', 'comment'])]
class SightingConfirmation extends Model
{
    public function sighting(): BelongsTo
    {
        return $this->belongsTo(Sighting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
