<?php

namespace App\Policies;

use App\Enums\RoomRole;
use App\Models\RoomMessage;
use App\Models\User;

class RoomMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, RoomMessage $roomMessage): bool
    {
        return $this->isRoomMember($user, $roomMessage->room_id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RoomMessage $roomMessage): bool
    {
        return $this->ownsMessage($user, $roomMessage)
            || $this->canModerateRoom($user, $roomMessage->room_id);
    }

    public function delete(User $user, RoomMessage $roomMessage): bool
    {
        return $this->ownsMessage($user, $roomMessage)
            || $this->canModerateRoom($user, $roomMessage->room_id);
    }

    public function restore(User $user, RoomMessage $roomMessage): bool
    {
        return false;
    }

    public function forceDelete(User $user, RoomMessage $roomMessage): bool
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

    private function ownsMessage(User $user, RoomMessage $roomMessage): bool
    {
        return $roomMessage->member()
            ->whereBelongsTo($user)
            ->exists();
    }
}
