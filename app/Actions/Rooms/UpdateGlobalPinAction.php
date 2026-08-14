<?php

namespace App\Actions\Rooms;

use App\Events\Rooms\RoomGlobalPinUpdated;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateGlobalPinAction
{
    public function __construct(private FindAuthorizedRoomMemberAction $findMember) {}

    /**
     * @param  array{room_member_id:int|null}  $data
     */
    public function handle(Room $room, ?User $user, ?string $guestId, array $data): ?RoomMember
    {
        $actor = $this->findMember->handle($room, $user, $guestId);

        if (! $actor->isModerator()) {
            throw new AuthorizationException('Only room moderators may update the global pin.');
        }

        $target = $this->targetMember($room, $data['room_member_id']);

        $room->forceFill([
            'global_pinned_room_member_id' => $target?->id,
        ])->save();
        $room->refresh();

        RoomGlobalPinUpdated::dispatch($room, $target, $actor);

        return $target;
    }

    private function targetMember(Room $room, ?int $memberId): ?RoomMember
    {
        if ($memberId === null) {
            return null;
        }

        $member = $room->members()
            ->whereKey($memberId)
            ->whereNull('left_at')
            ->first();

        if (! $member instanceof RoomMember) {
            throw new AuthorizationException('The pinned member must be active in this room.');
        }

        $member->setRelation('room', $room);

        return $member;
    }
}
