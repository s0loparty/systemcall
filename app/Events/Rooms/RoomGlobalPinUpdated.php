<?php

namespace App\Events\Rooms;

use App\Models\Room;
use App\Models\RoomMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomGlobalPinUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Room $room,
        public ?RoomMember $member,
        public RoomMember $moderator,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.'.$this->room->public_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.global-pin.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'global_pin_updated',
            'room_public_id' => $this->room->public_id,
            'global_pinned_room_member_id' => $this->member?->id,
            'member' => $this->member instanceof RoomMember ? [
                'id' => $this->member->id,
                'display_name' => $this->member->display_name,
            ] : null,
            'moderator_room_member_id' => $this->moderator->id,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
