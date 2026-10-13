<?php

namespace App\Notifications;

use App\Models\Identification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class IdentificationEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Identification $identification)
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
     * @return array{title: string, body: string, url: string, identification_id: int}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Identification needs an expert',
            'body' => $this->identification->user->name.' escalated a low-confidence '.$this->identification->plant_part.' scan.',
            'url' => route('review.index', ['tab' => 'identifications']),
            'identification_id' => $this->identification->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
