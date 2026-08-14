<?php

namespace App\Actions\Rooms;

use App\Events\Rooms\RoomMemberSubroomChanged;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomSubroom;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class SwitchRoomSubroomAction
{
    public function __construct(private FindAuthorizedRoomMemberAction $findMember) {}

    /**
     * @param  array{room_subroom_public_id:string|null}  $data
     * @return array{member:RoomMember,subroom:RoomSubroom|null}
     */
    public function handle(Room $room, ?User $user, ?string $guestId, array $data): array
    {
        $member = $this->findMember->handle($room, $user, $guestId);
        $subroom = $this->targetSubroom($room, $data['room_subroom_public_id']);

        $member->forceFill([
            'room_subroom_id' => $subroom?->id,
        ])->save();
        $member->refresh();
        $member->setRelation('room', $room);
        $member->setRelation('subroom', $subroom);

        RoomMemberSubroomChanged::dispatch($member, $subroom);

        return [
            'member' => $member,
            'subroom' => $subroom,
        ];
    }

    private function targetSubroom(Room $room, ?string $publicId): ?RoomSubroom
    {
        if ($publicId === null) {
            return null;
        }

        $subroom = $room->subrooms()
            ->where('public_id', $publicId)
            ->first();

        if (! $subroom instanceof RoomSubroom) {
            throw new AuthorizationException('The target subroom does not belong to this room.');
        }

        $subroom->setRelation('room', $room);

        return $subroom;
    }
}
