<?php

namespace App\Actions\Rooms;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class FindAuthorizedRoomMemberAction
{
    public function handle(Room $room, ?User $user, ?string $guestId): RoomMember
    {
        if ($room->status !== RoomStatus::Active) {
            throw new AuthorizationException('Room is not active.');
        }

        $member = $this->memberForIdentity($room, $user, $guestId);

        if (! $member instanceof RoomMember) {
            throw new AuthorizationException('Join the room before requesting a media token.');
        }

        if ($this->isBanned($room, $user, $guestId)) {
            throw new AuthorizationException('This participant is banned from the room.');
        }

        if ($room->waiting_room_enabled) {
            throw new AuthorizationException('Waiting-room approval is required before media access.');
        }

        return $member;
    }

    private function memberForIdentity(Room $room, ?User $user, ?string $guestId): ?RoomMember
    {
        $query = $room->members()->whereNull('left_at');

        if ($user instanceof User) {
            return $query->whereBelongsTo($user)->first();
        }

        if ($guestId === null) {
            return null;
        }

        return $query->where('guest_id', $guestId)->first();
    }

    private function isBanned(Room $room, ?User $user, ?string $guestId): bool
    {
        return $room->bans()
            ->where(function ($query) use ($user, $guestId): void {
                if ($user instanceof User) {
                    $query->whereBelongsTo($user);

                    return;
                }

                $query->where('guest_id', $guestId);
            })
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
