<?php

namespace App\Policies;

use App\Models\Plant;
use App\Models\User;

/**
 * Plant profiles are curated reference data: only expert reviewers and admins edit them.
 */
class PlantPolicy
{
    public function create(User $user): bool
    {
        return $user->isReviewer();
    }

    public function update(User $user, Plant $plant): bool
    {
        return $user->isReviewer();
    }

    public function delete(User $user, Plant $plant): bool
    {
        return $user->isAdmin();
    }
}
