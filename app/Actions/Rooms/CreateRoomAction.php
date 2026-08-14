<?php

namespace App\Actions\Rooms;

use App\Enums\RoomRole;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateRoomAction
{
    /**
     * @param  array{name:string,description?:string|null,visibility:string,password?:string|null,waiting_room_enabled?:bool,max_participants?:int|null}  $data
     */
    public function handle(User $owner, array $data): Room
    {
        $room = Room::create([
            'owner_id' => $owner->id,
            'public_id' => $this->publicId(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
            'password_hash' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null,
            'waiting_room_enabled' => $data['waiting_room_enabled'] ?? false,
            'max_participants' => $data['max_participants'] ?? null,
        ]);

        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $owner->id,
            'display_name' => $owner->name,
            'role' => RoomRole::Owner,
            'livekit_identity' => $this->livekitIdentity(),
            'joined_at' => now(),
        ]);

        return $room;
    }

    private function publicId(): string
    {
        do {
            $publicId = Str::random(24);
        } while (Room::where('public_id', $publicId)->exists());

        return $publicId;
    }

    private function livekitIdentity(): string
    {
        return 'room-member:'.Str::uuid();
    }
}
