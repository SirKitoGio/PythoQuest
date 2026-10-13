<?php

namespace App\Notifications;

use App\Models\Sighting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewSubmissionForReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Sighting $sighting, public bool $isResubmission = false)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array{title: string, body: string, url: string, sighting_id: int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => ($this->isResubmission ? 'Corrected sighting: ' : 'New sighting to review: ').$this->sighting->plant->common_name,
            'body' => 'Submitted by '.$this->sighting->user->name.'.',
            'url' => route('review.index'),
            'sighting_id' => $this->sighting->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
