<?php

namespace App\Notifications;

use App\Models\Sighting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a submitter their sighting was approved, rejected or needs correction (FR-13).
 */
class SightingReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Sighting $sighting)
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
     * @return array{title: string, body: string, url: string, sighting_id: int, status: string}
     */
    public function toArray(object $notifiable): array
    {
        $plant = $this->sighting->plant->common_name;

        return [
            'title' => match ($this->sighting->status) {
                'trusted' => "Your {$plant} sighting is now trusted",
                'needs correction' => "Your {$plant} sighting needs correction",
                'rejected' => "Your {$plant} sighting was rejected",
                default => "Your {$plant} sighting was reviewed",
            },
            'body' => $this->sighting->review_note ?? 'An expert reviewer has assessed your submission.',
            'url' => route('sightings.show', $this->sighting),
            'sighting_id' => $this->sighting->id,
            'status' => $this->sighting->status,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
