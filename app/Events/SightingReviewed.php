<?php

namespace App\Events;

use App\Models\Sighting;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SightingReviewed implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Sighting $sighting, public User $reviewer, public string $previousStatus) {}
}
