<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Identification;
use App\Models\User;
use App\Notifications\IdentificationEscalatedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Sends a low-confidence or disputed scan to the expert review queue (FR-07).
 */
class EscalateIdentification
{
    public function handle(Identification $identification): Identification
    {
        $identification->update(['escalated_at' => now()]);

        Notification::send(
            User::whereIn('role', [UserRole::Reviewer, UserRole::Admin])->whereKeyNot($identification->user_id)->get(),
            new IdentificationEscalatedNotification($identification),
        );

        return $identification;
    }
}
