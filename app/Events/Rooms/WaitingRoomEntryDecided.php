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
                'room_public_id' => $this->member->room->public_id,
                'display_name' => $this->member->display_name,
                'role' => $this->member->role->value,
                'livekit_identity' => $this->member->livekit_identity,
                'guest' => $this->member->user_id === null,
                'can_publish_audio' => $this->member->can_publish_audio,
                'can_publish_video' => $this->member->can_publish_video,
                'can_publish_screen' => $this->member->can_publish_screen,
                'hand_raised_at' => $this->member->hand_raised_at?->toISOString(),
                'left_at' => $this->member->left_at?->toISOString(),
            ] : null,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
