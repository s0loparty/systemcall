<?php

namespace App\Events\Rooms;

use App\Models\RoomMember;
use App\Models\RoomSubroom;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomMemberSubroomChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RoomMember $member,
        public ?RoomSubroom $subroom,
    ) {}

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
        return 'room.member.subroom-changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'member_subroom_changed',
            'room_public_id' => $this->member->room->public_id,
            'member' => [
                'id' => $this->member->id,
                'room_public_id' => $this->member->room->public_id,
                'room_subroom_id' => $this->member->room_subroom_id,
                'display_name' => $this->member->display_name,
                'role' => $this->member->role->value,
                'livekit_identity' => $this->member->livekit_identity,
                'guest' => $this->member->user_id === null,
                'can_publish_audio' => $this->member->can_publish_audio,
                'can_publish_video' => $this->member->can_publish_video,
                'can_publish_screen' => $this->member->can_publish_screen,
                'hand_raised_at' => $this->member->hand_raised_at?->toISOString(),
                'left_at' => $this->member->left_at?->toISOString(),
            ],
            'subroom' => $this->subroom instanceof RoomSubroom ? [
                'id' => $this->subroom->id,
                'room_public_id' => $this->member->room->public_id,
                'public_id' => $this->subroom->public_id,
                'name' => $this->subroom->name,
                'position' => $this->subroom->position,
                'is_default' => $this->subroom->is_default,
            ] : null,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
