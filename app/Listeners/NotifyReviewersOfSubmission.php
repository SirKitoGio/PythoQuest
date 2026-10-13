<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\SightingSubmitted;
use App\Models\User;
use App\Notifications\NewSubmissionForReviewNotification;
use Illuminate\Support\Facades\Notification;

class NotifyReviewersOfSubmission
{
    /**
     * Handle the event.
     */
    public function handle(SightingSubmitted $event): void
    {
        $reviewers = User::whereIn('role', [UserRole::Reviewer, UserRole::Admin])
            ->whereKeyNot($event->sighting->user_id)
            ->get();

        Notification::send($reviewers, new NewSubmissionForReviewNotification($event->sighting, $event->isResubmission));
    }
}
