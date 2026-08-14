<?php

namespace App\Events\Rooms;

use App\Models\RoomSubroom;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomSubroomCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public RoomSubroom $subroom) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.'.$this->subroom->room->public_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.subroom.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'subroom_created',
            'room_public_id' => $this->subroom->room->public_id,
            'subroom' => [
                'id' => $this->subroom->id,
                'room_public_id' => $this->subroom->room->public_id,
                'public_id' => $this->subroom->public_id,
                'name' => $this->subroom->name,
                'position' => $this->subroom->position,
                'is_default' => $this->subroom->is_default,
            ],
            'occurred_at' => now()->toISOString(),
        ];
    }
}
