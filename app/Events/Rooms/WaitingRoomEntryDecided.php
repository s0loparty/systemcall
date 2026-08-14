<?php

namespace App\Events\Rooms;

use App\Models\RoomMember;
use App\Models\WaitingRoomMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaitingRoomEntryDecided implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WaitingRoomMember $entry,
        public ?RoomMember $member = null,
    ) {}

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
        return 'waiting-room.entry.decided';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'waiting_room_entry_decided',
            'room_public_id' => $this->entry->room->public_id,
            'waiting_room' => [
                'id' => $this->entry->id,
                'display_name' => $this->entry->display_name,
                'status' => $this->entry->status->value,
                'guest' => $this->entry->user_id === null,
            ],
            'member' => $this->member instanceof RoomMember ? [
                'id' => $this->member->id,
                'display_name' => $this->member->display_name,
                'role' => $this->member->role->value,
                'guest' => $this->member->user_id === null,
            ] : null,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
