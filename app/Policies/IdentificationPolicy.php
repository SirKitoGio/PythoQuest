<?php

namespace App\Policies;

use App\Models\Identification;
use App\Models\User;

class IdentificationPolicy
{
    public function view(User $user, Identification $identification): bool
    {
        return $user->id === $identification->user_id || $user->isReviewer();
    }

    /**
     * Only finished, low-confidence or ambiguous scans can be escalated, and only once.
     */
    public function escalate(User $user, Identification $identification): bool
    {
        return $user->id === $identification->user_id
            && $identification->isFinished()
            && $identification->escalated_at === null;
    }

    public function review(User $user, Identification $identification): bool
    {
        return $user->isReviewer()
            && $user->id !== $identification->user_id
            && $identification->escalated_at !== null;
    }
}
