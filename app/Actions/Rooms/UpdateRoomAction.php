<?php

namespace App\Actions\Rooms;

use App\Models\Room;
use Illuminate\Support\Facades\Hash;

class UpdateRoomAction
{
    /**
     * @param  array{name?:string,description?:string|null,visibility?:string,password?:string|null,waiting_room_enabled?:bool,max_participants?:int|null}  $data
     */
    public function handle(Room $room, array $data): Room
    {
        $attributes = [
            'name' => $data['name'] ?? $room->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $room->description,
            'visibility' => $data['visibility'] ?? $room->visibility,
            'waiting_room_enabled' => $data['waiting_room_enabled'] ?? $room->waiting_room_enabled,
            'max_participants' => array_key_exists('max_participants', $data) ? $data['max_participants'] : $room->max_participants,
        ];

        if (array_key_exists('password', $data)) {
            $attributes['password_hash'] = filled($data['password']) ? Hash::make($data['password']) : null;
        }

        $room->update($attributes);

        return $room->refresh();
    }
}
