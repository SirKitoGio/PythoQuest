<?php

namespace App\Actions;

use App\Events\SightingReviewed;
use App\Models\AuditLog;
use App\Models\Sighting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records an expert decision. The row is locked so concurrent reviews resolve in commit
 * order, and the latest expert decision always wins over community confirmations (FR-11).
 */
class ReviewSighting
{
    public function handle(Sighting $sighting, User $reviewer, string $decision, ?string $note = null): Sighting
    {
        return DB::transaction(function () use ($sighting, $reviewer, $decision, $note) {
            $locked = Sighting::whereKey($sighting->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $locked->status;

            $locked->forceFill([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            AuditLog::record('sighting.reviewed', $locked, [
                'status' => [$previousStatus, $decision],
                'note' => $note,
                'confirmations' => $locked->confirmations()->count(),
            ], $reviewer);

            SightingReviewed::dispatch($locked, $reviewer, $previousStatus);

            return $locked;
        });
    }
}
