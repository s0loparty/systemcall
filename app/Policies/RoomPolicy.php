<?php

namespace App\Policies;

use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Room $room): bool
    {
        return ($room->visibility === RoomVisibility::Public
            && $room->status === RoomStatus::Active)
            || $room->isOwnedBy($user)
            || $this->isRoomMember($user, $room);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Room $room): bool
    {
        return $room->isOwnedBy($user);
    }

    public function delete(User $user, Room $room): bool
    {
        return $room->isOwnedBy($user);
    }

    public function restore(User $user, Room $room): bool
    {
        return false;
    }

    public function forceDelete(User $user, Room $room): bool
    {
        return false;
    }

    private function isRoomMember(User $user, Room $room): bool
    {
        return $user->roomMemberships()
            ->whereBelongsTo($room)
            ->whereNull('left_at')
            ->exists();
    }
}
