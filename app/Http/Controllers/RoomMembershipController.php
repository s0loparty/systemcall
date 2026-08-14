<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\JoinRoomAction;
use App\Http\Requests\Rooms\JoinRoomRequest;
use App\Models\Room;
use App\Models\RoomMember;
use App\Support\GuestIdentity;
use Illuminate\Http\JsonResponse;

class RoomMembershipController extends Controller
{
    public function store(
        JoinRoomRequest $request,
        Room $room,
        JoinRoomAction $joinRoom,
        GuestIdentity $guestIdentity,
    ): JsonResponse {
        $user = $request->user();
        $guestId = $user === null ? $guestIdentity->getOrCreate($request) : null;

        $member = $joinRoom->handle($room, $user, $guestId, $request->joinData());

        return response()->json(['member' => $this->memberPayload($member)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPayload(RoomMember $member): array
    {
        return [
            'id' => $member->id,
            'room_public_id' => $member->room->public_id,
            'display_name' => $member->display_name,
            'role' => $member->role->value,
            'livekit_identity' => $member->livekit_identity,
            'guest' => $member->user_id === null,
        ];
    }
}
