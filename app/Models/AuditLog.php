<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only trail of moderation and administrative actions.
 */
#[Fillable(['user_id', 'action', 'auditable_type', 'auditable_id', 'changes'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public static function record(string $action, ?Model $subject = null, array $changes = [], ?User $actor = null): self
    {
        return self::create([
            'user_id' => ($actor ?? auth()->user())?->getKey(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'changes' => $changes ?: null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
