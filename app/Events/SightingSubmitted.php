<?php

namespace App\Events;

use App\Models\Sighting;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SightingSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Sighting $sighting, public bool $isResubmission = false) {}
}
