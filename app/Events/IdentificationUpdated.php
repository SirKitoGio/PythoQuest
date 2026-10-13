<?php

namespace App\Events;

use App\Models\Identification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes each identification state change (processing, completed, failed) to its owner.
 */
class IdentificationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Identification $identification) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->identification->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'identification.updated';
    }

    /**
     * @return array{id: int, status: string, needs_more_images: bool, top_confidence: ?float}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->identification->id,
            'status' => $this->identification->status,
            'needs_more_images' => $this->identification->needs_more_images,
            'top_confidence' => $this->identification->top_confidence,
        ];
    }
}
