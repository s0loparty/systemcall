<?php

namespace App\Broadcasting;

use App\Enums\RoomStatus;
use App\Enums\WaitingRoomStatus;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use App\Support\GuestIdentity;

class RoomApplicationChannel
{
    public function __construct(private GuestIdentity $guestIdentity) {}

    public function join(?User $user, string $roomPublicId): bool
    {
        $room = Room::query()
            ->where('public_id', $roomPublicId)
            ->first();

        if (! $room instanceof Room || $room->status !== RoomStatus::Active) {
            return false;
        }

        if ($user instanceof User && $room->owner()->is($user)) {
            return true;
        }

        $guestId = $user instanceof User ? null : $this->guestIdentity->current(request());
        $member = $this->memberForIdentity($room, $user, $guestId);

        if (! $member instanceof RoomMember && ! $this->hasWaitingRoomAccess($room, $user, $guestId)) {
            return false;
        }

        if (
            $room->waiting_room_enabled
            && $member instanceof RoomMember
            && ! $member->isModerator()
            && ! $this->hasApprovedWaitingRoomEntry($room, $user, $guestId)
        ) {
            return false;
        }

        return ! $this->isBanned($room, $user, $guestId);
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

    private function hasWaitingRoomAccess(Room $room, ?User $user, ?string $guestId): bool
    {
        if (! $room->waiting_room_enabled) {
            return false;
        }

        if (! $user instanceof User && $guestId === null) {
            return false;
        }

        return $room->waitingMembers()
            ->whereIn('status', [WaitingRoomStatus::Pending, WaitingRoomStatus::Approved])
            ->where(function ($query) use ($user, $guestId): void {
                if ($user instanceof User) {
                    $query->whereBelongsTo($user);

                    return;
                }

                $query->where('guest_id', $guestId);
            })
            ->exists();
    }

    private function hasApprovedWaitingRoomEntry(Room $room, ?User $user, ?string $guestId): bool
    {
        if (! $room->waiting_room_enabled) {
            return false;
        }

        if (! $user instanceof User && $guestId === null) {
            return false;
        }

        return $room->waitingMembers()
            ->where('status', WaitingRoomStatus::Approved)
            ->where(function ($query) use ($user, $guestId): void {
                if ($user instanceof User) {
                    $query->whereBelongsTo($user);

                    return;
                }

                $query->where('guest_id', $guestId);
            })
            ->exists();
    }
}
