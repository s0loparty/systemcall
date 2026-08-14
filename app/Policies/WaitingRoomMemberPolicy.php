<?php

namespace App\Policies;

use App\Enums\RoomRole;
use App\Models\User;
use App\Models\WaitingRoomMember;

class WaitingRoomMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, WaitingRoomMember $waitingRoomMember): bool
    {
        return $waitingRoomMember->user_id === $user->id
            || $this->canModerateRoom($user, $waitingRoomMember->room_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WaitingRoomMember $waitingRoomMember): bool
    {
        return $this->canModerateRoom($user, $waitingRoomMember->room_id);
    }

    public function delete(User $user, WaitingRoomMember $waitingRoomMember): bool
    {
        return $this->canModerateRoom($user, $waitingRoomMember->room_id);
    }

    public function restore(User $user, WaitingRoomMember $waitingRoomMember): bool
    {
        return false;
    }

    public function forceDelete(User $user, WaitingRoomMember $waitingRoomMember): bool
    {
        return false;
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
