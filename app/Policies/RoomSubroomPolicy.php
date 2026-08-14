<?php

namespace App\Policies;

use App\Enums\RoomRole;
use App\Models\RoomSubroom;
use App\Models\User;

class RoomSubroomPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, RoomSubroom $roomSubroom): bool
    {
        return $this->isRoomMember($user, $roomSubroom->room_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RoomSubroom $roomSubroom): bool
    {
        return $this->canModerateRoom($user, $roomSubroom->room_id);
    }

    public function delete(User $user, RoomSubroom $roomSubroom): bool
    {
        return $this->canModerateRoom($user, $roomSubroom->room_id);
    }

    public function restore(User $user, RoomSubroom $roomSubroom): bool
    {
        return false;
    }

    public function forceDelete(User $user, RoomSubroom $roomSubroom): bool
    {
        return false;
    }

    private function isRoomMember(User $user, int $roomId): bool
    {
        return $user->roomMemberships()
            ->where('room_id', $roomId)
            ->whereNull('left_at')
            ->exists();
    }

    private function canModerateRoom(User $user, int $roomId): bool
    {
        return $user->roomMemberships()
            ->where('room_id', $roomId)
            ->whereNull('left_at')
            ->whereIn('role', [RoomRole::Owner->value, RoomRole::Moderator->value])
            ->exists();
    }
}
