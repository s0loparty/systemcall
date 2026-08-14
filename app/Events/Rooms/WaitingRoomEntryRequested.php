<?php

namespace App\Events\Rooms;

use App\Models\WaitingRoomMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaitingRoomEntryRequested implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public WaitingRoomMember $entry) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.'.$this->entry->room->public_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'waiting-room.entry.requested';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'waiting_room_entry_requested',
            'room_public_id' => $this->entry->room->public_id,
            'waiting_room' => [
                'id' => $this->entry->id,
                'display_name' => $this->entry->display_name,
                'status' => $this->entry->status->value,
                'guest' => $this->entry->user_id === null,
            ],
            'occurred_at' => now()->toISOString(),
        ];
    }
}
