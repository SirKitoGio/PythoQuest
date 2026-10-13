<?php

namespace App\Policies;

use App\Models\Sighting;
use App\Models\User;

class SightingPolicy
{
    /**
     * Owners and expert reviewers can see a sighting's full detail, including exact coordinates.
     */
    public function view(User $user, Sighting $sighting): bool
    {
        return $user->id === $sighting->user_id || $user->isReviewer();
    }

    public function update(User $user, Sighting $sighting): bool
    {
        return $user->id === $sighting->user_id;
    }

    public function delete(User $user, Sighting $sighting): bool
    {
        return $user->id === $sighting->user_id;
    }

    /**
     * Reviewers may not approve their own submissions.
     */
    public function review(User $user, Sighting $sighting): bool
    {
        return $user->isReviewer() && $user->id !== $sighting->user_id;
    }

    /**
     * Community members confirm other people's open, non-private records.
     */
    public function confirm(User $user, Sighting $sighting): bool
    {
        return $user->id !== $sighting->user_id
            && $sighting->visibility !== 'private'
            && in_array($sighting->status, ['pending', 'needs correction', 'trusted'], true);
    }
}
