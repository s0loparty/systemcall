<?php

namespace App\Policies;

use App\Enums\RoomRole;
use App\Models\RoomMember;
use App\Models\User;

class RoomMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, RoomMember $roomMember): bool
    {
        return $roomMember->user_id === $user->id
            || $this->canModerateRoom($user, $roomMember);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RoomMember $roomMember): bool
    {
        return $this->canModerateRoom($user, $roomMember);
    }

    public function delete(User $user, RoomMember $roomMember): bool
    {
        return $this->canModerateRoom($user, $roomMember);
    }

    public function restore(User $user, RoomMember $roomMember): bool
    {
        return false;
    }

    public function forceDelete(User $user, RoomMember $roomMember): bool
    {
        return false;
    }

    private function canModerateRoom(User $user, RoomMember $roomMember): bool
    {
        return $user->roomMemberships()
            ->whereBelongsTo($roomMember->room)
            ->whereNull('left_at')
            ->whereIn('role', [RoomRole::Owner->value, RoomRole::Moderator->value])
            ->exists();
    }
}
