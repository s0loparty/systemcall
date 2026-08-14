<?php

namespace App\Actions\Rooms;

use App\Events\Rooms\RoomMemberHandUpdated;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;

class UpdateRaisedHandAction
{
    public function __construct(private FindAuthorizedRoomMemberAction $findMember) {}

    /**
     * @param  array{raised:bool}  $data
     */
    public function handle(Room $room, ?User $user, ?string $guestId, array $data): RoomMember
    {
        $member = $this->findMember->handle($room, $user, $guestId);

        $member->forceFill([
            'hand_raised_at' => $data['raised'] ? now() : null,
        ])->save();
        $member->refresh();
        $member->setRelation('room', $room);

        RoomMemberHandUpdated::dispatch($member);

        return $member;
    }
}
