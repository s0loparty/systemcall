<?php

namespace App\Actions\Rooms;

use App\Events\Rooms\RoomMessageCreated;
use App\Models\Room;
use App\Models\RoomMessage;
use App\Models\User;

class SendRoomMessageAction
{
    public function __construct(private FindAuthorizedRoomMemberAction $findMember) {}

    /**
     * @param  array{body:string}  $data
     */
    public function handle(Room $room, ?User $user, ?string $guestId, array $data): RoomMessage
    {
        $member = $this->findMember->handle($room, $user, $guestId);

        $message = RoomMessage::create([
            'room_id' => $room->id,
            'room_member_id' => $member->id,
            'type' => 'text',
            'body' => $data['body'],
        ]);

        $message->setRelation('room', $room);
        $message->setRelation('member', $member);

        RoomMessageCreated::dispatch($message);

        return $message;
    }
}
