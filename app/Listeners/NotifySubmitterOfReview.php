<?php

namespace App\Listeners;

use App\Events\SightingReviewed;
use App\Notifications\SightingReviewedNotification;

class NotifySubmitterOfReview
{
    /**
     * Handle the event.
     */
    public function handle(SightingReviewed $event): void
    {
        $event->sighting->user->notify(new SightingReviewedNotification($event->sighting));
    }
}
