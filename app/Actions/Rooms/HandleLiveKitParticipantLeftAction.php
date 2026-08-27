<?php

namespace App\Actions\Rooms;

use App\Models\RoomMember;
use App\Services\LiveKit\LiveKitWebhookEvent;
use Illuminate\Support\Str;

class HandleLiveKitParticipantLeftAction
{
    public function __construct(private LeaveRoomMemberAction $leaveMember) {}

    public function handle(LiveKitWebhookEvent $event): bool
    {
        if ($event->event !== 'participant_left') {
            return false;
        }

        $memberId = $event->participantMetadata['room_member_id'] ?? null;
        $mediaSessionId = $event->participantMetadata['media_session_id'] ?? null;

        if (
            ! is_numeric($memberId)
            || ! is_string($mediaSessionId)
            || ! Str::isUuid($mediaSessionId)
            || $event->participantIdentity === null
            || $event->roomName === null
        ) {
            return false;
        }

        $member = RoomMember::query()->find((int) $memberId);

        if (! $member instanceof RoomMember) {
            return false;
        }

        return $this->leaveMember->handle(
            $member,
            expectedMediaSessionId: $mediaSessionId,
            expectedLiveKitRoomName: $event->roomName,
            expectedLiveKitIdentity: $event->participantIdentity,
        );
    }
}
