<?php

namespace App\Actions\Rooms;

use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Enums\WaitingRoomStatus;
use App\Events\Rooms\WaitingRoomEntryRequested;
use App\Models\Room;
use App\Models\User;
use App\Models\WaitingRoomMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RequestWaitingRoomEntryAction
{
    /**
     * @param  array{display_name?:string|null,password?:string|null,guest_id?:never}  $data
     */
    public function handle(Room $room, ?User $user, ?string $guestId, array $data): WaitingRoomMember
    {
        if ($room->status !== RoomStatus::Active) {
            throw new AuthorizationException('Room is not active.');
        }

        if (! $room->waiting_room_enabled) {
            throw new AuthorizationException('Waiting room is not enabled.');
        }

        if (! $this->canAttemptJoin($room, $user)) {
            throw new AuthorizationException('This room is private.');
        }

        if ($room->password_hash !== null && ! Hash::check($data['password'] ?? '', $room->password_hash)) {
            throw ValidationException::withMessages([
                'password' => __('The room password is incorrect.'),
            ]);
        }

        if ($this->isBanned($room, $user, $guestId)) {
            throw new AuthorizationException('This participant is banned from the room.');
        }

        return DB::transaction(function () use ($room, $user, $guestId, $data): WaitingRoomMember {
            $attributes = [
                'room_id' => $room->id,
            ];

            if ($user instanceof User) {
                $attributes['user_id'] = $user->id;
            } else {
                $attributes['guest_id'] = $guestId;
            }

            $entry = WaitingRoomMember::query()
                ->where($attributes)
                ->where('status', WaitingRoomStatus::Pending)
                ->lockForUpdate()
                ->first();

            if (! $entry instanceof WaitingRoomMember) {
                $entry = WaitingRoomMember::query()->create([
                    ...$attributes,
                    'display_name' => $this->displayName($user, $data),
                    'status' => WaitingRoomStatus::Pending,
                ]);

                WaitingRoomEntryRequested::dispatch($entry);
            }

            return $entry->refresh();
        });
    }

    private function canAttemptJoin(Room $room, ?User $user): bool
    {
        if ($room->visibility === RoomVisibility::Public) {
            return true;
        }

        if ($room->password_hash !== null) {
            return true;
        }

        if (! $user instanceof User) {
            return false;
        }

        return $room->members()
            ->whereBelongsTo($user)
            ->whereNull('left_at')
            ->exists();
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

    /**
     * @param  array{display_name?:string|null,password?:string|null}  $data
     */
    private function displayName(?User $user, array $data): string
    {
        if ($user instanceof User) {
            return $user->name;
        }

        return (string) ($data['display_name'] ?? 'Guest');
    }
}
