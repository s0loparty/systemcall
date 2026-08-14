<?php

namespace App\Policies;

use App\Enums\RoomRole;
use App\Models\RoomBan;
use App\Models\User;

class RoomBanPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, RoomBan $roomBan): bool
    {
        return $this->canModerateRoom($user, $roomBan->room_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RoomBan $roomBan): bool
    {
        return false;
    }

    public function delete(User $user, RoomBan $roomBan): bool
    {
        return $this->canModerateRoom($user, $roomBan->room_id);
    }

    public function restore(User $user, RoomBan $roomBan): bool
    {
        return false;
    }

    public function forceDelete(User $user, RoomBan $roomBan): bool
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
