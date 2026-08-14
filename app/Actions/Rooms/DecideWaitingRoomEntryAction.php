<?php

namespace App\Actions\Rooms;

use App\Enums\RoomRole;
use App\Enums\WaitingRoomStatus;
use App\Events\Rooms\RoomMemberJoined;
use App\Events\Rooms\WaitingRoomEntryDecided;
use App\Models\RoomMember;
use App\Models\User;
use App\Models\WaitingRoomMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DecideWaitingRoomEntryAction
{
    public function approve(WaitingRoomMember $entry, User $moderator): WaitingRoomMember
    {
        return $this->decide($entry, $moderator, WaitingRoomStatus::Approved);
    }

    public function reject(WaitingRoomMember $entry, User $moderator): WaitingRoomMember
    {
        return $this->decide($entry, $moderator, WaitingRoomStatus::Rejected);
    }

    private function decide(WaitingRoomMember $entry, User $moderator, WaitingRoomStatus $status): WaitingRoomMember
    {
        return DB::transaction(function () use ($entry, $moderator, $status): WaitingRoomMember {
            $entry = WaitingRoomMember::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->status !== WaitingRoomStatus::Pending) {
                return $entry;
            }

            $entry->forceFill([
                'status' => $status,
                'decided_by_user_id' => $moderator->id,
                'decided_at' => now(),
            ])->save();

            $member = null;

            if ($status === WaitingRoomStatus::Approved) {
                $member = $this->createMember($entry);
                RoomMemberJoined::dispatch($member);
            }

            WaitingRoomEntryDecided::dispatch($entry->refresh(), $member);

            return $entry;
        });
    }

    private function createMember(WaitingRoomMember $entry): RoomMember
    {
        $attributes = [
            'room_id' => $entry->room_id,
            'left_at' => null,
        ];

        if ($entry->user_id !== null) {
            $attributes['user_id'] = $entry->user_id;
        } else {
            $attributes['guest_id'] = $entry->guest_id;
        }

        $member = RoomMember::query()
            ->where($attributes)
            ->lockForUpdate()
            ->first();

        if ($member instanceof RoomMember) {
            return $member;
        }

        return RoomMember::query()->create([
            ...$attributes,
            'display_name' => $entry->display_name,
            'role' => RoomRole::Participant,
            'livekit_identity' => 'room-member:'.Str::uuid(),
            'joined_at' => now(),
        ]);
    }
}
