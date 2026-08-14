<?php

namespace App\Events\Rooms;

use App\Models\RoomMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomMessageCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public RoomMessage $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.'.$this->message->room->public_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.message.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'message_created',
            'room_public_id' => $this->message->room->public_id,
            'message' => [
                'id' => $this->message->id,
                'room_public_id' => $this->message->room->public_id,
                'room_member_id' => $this->message->room_member_id,
                'display_name' => $this->message->room_member_id === null
                    ? 'System'
                    : $this->message->member->display_name,
                'body' => $this->message->body,
                'message_type' => $this->message->type,
                'created_at' => $this->message->created_at?->toISOString(),
            ],
            'occurred_at' => now()->toISOString(),
        ];
    }
}
