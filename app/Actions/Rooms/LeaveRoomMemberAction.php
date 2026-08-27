<?php

namespace App\Actions\Rooms;

use App\Events\Rooms\RoomMemberLeft;
use App\Models\RoomMember;
use Illuminate\Support\Facades\DB;

class LeaveRoomMemberAction
{
    public function handle(
        RoomMember $member,
        ?string $expectedMediaSessionId = null,
        ?string $expectedLiveKitRoomName = null,
        ?string $expectedLiveKitIdentity = null,
    ): bool {
        return DB::transaction(function () use (
            $member,
            $expectedMediaSessionId,
            $expectedLiveKitRoomName,
            $expectedLiveKitIdentity,
        ): bool {
            /** @var RoomMember|null $lockedMember */
            $lockedMember = RoomMember::query()
                ->with(['room', 'subroom'])
                ->lockForUpdate()
                ->find($member->id);

            if (! $lockedMember instanceof RoomMember || $lockedMember->left_at !== null) {
                return false;
            }

            if (
                $expectedMediaSessionId !== null
                && $lockedMember->media_session_id !== $expectedMediaSessionId
            ) {
                return false;
            }

            if (
                $expectedLiveKitIdentity !== null
                && $lockedMember->livekit_identity !== $expectedLiveKitIdentity
            ) {
                return false;
            }

            if (
                $expectedLiveKitRoomName !== null
                && $this->liveKitRoomName($lockedMember) !== $expectedLiveKitRoomName
            ) {
                return false;
            }

            $lockedMember->forceFill([
                'hand_raised_at' => null,
                'left_at' => now(),
            ])->save();
            $lockedMember->refresh();

            if ($lockedMember->room->global_pinned_room_member_id === $lockedMember->id) {
                $lockedMember->room->forceFill(['global_pinned_room_member_id' => null])->save();
            }

            RoomMemberLeft::dispatch($lockedMember);

            return true;
        });
    }

    private function liveKitRoomName(RoomMember $member): string
    {
        return $member->subroom?->livekit_room_name ?? 'room-'.$member->room->public_id;
    }
}
