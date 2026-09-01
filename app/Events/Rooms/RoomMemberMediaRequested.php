<?php

namespace App\Events\Rooms;

use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomMemberMediaRequested implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public RoomMember $member, public User $moderator, public string $mediaKind) {}

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
        return 'room.member.media-requested';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'member_media_requested',
            'room_public_id' => $this->member->room->public_id,
            'member' => $this->memberPayload(),
            'media_kind' => $this->mediaKind,
            'moderator' => [
                'id' => $this->moderator->id,
                'name' => $this->moderator->name,
            ],
            'occurred_at' => now()->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPayload(): array
    {
        return [
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
        ];
    }
}
