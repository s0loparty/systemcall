<?php

namespace App\Events\Rooms;

use App\Models\RoomMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomMemberJoined implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public RoomMember $member) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.'.$this->member->room->public_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.member.joined';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'member_joined',
            'room_public_id' => $this->member->room->public_id,
            'member' => [
                'id' => $this->member->id,
                'display_name' => $this->member->display_name,
                'role' => $this->member->role->value,
                'guest' => $this->member->user_id === null,
            ],
            'occurred_at' => now()->toISOString(),
        ];
    }
}
