<?php

namespace App\Actions\Rooms;

use App\Enums\RoomRole;
use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JoinRoomAction
{
    /**
     * @param  array{display_name?:string|null,password?:string|null,guest_id?:never}  $data
     */
    public function handle(Room $room, ?User $user, ?string $guestId, array $data): RoomMember
    {
        if ($room->status !== RoomStatus::Active) {
            throw new AuthorizationException('Room is not active.');
        }

        if (! $this->canAttemptJoin($room, $user)) {
            throw new AuthorizationException('This room is private.');
        }

        if ($room->password_hash !== null && ! Hash::check($data['password'] ?? '', $room->password_hash)) {
            throw ValidationException::withMessages([
                'password' => __('The room password is incorrect.'),
            ]);
        }

        $attributes = [
            'room_id' => $room->id,
            'left_at' => null,
        ];

        if ($user instanceof User) {
            $attributes['user_id'] = $user->id;
        } else {
            $attributes['guest_id'] = $guestId;
        }

        $member = RoomMember::query()->firstOrNew($attributes);

        if (! $member->exists) {
            $member->forceFill([
                'display_name' => $this->displayName($user, $data),
                'role' => RoomRole::Participant,
                'livekit_identity' => 'room-member:'.Str::uuid(),
                'joined_at' => now(),
            ]);
        }

        $member->save();

        return $member->refresh();
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
