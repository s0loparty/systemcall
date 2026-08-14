<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\UpdateGlobalPinAction;
use App\Actions\Rooms\UpdateRaisedHandAction;
use App\Http\Requests\Rooms\UpdateGlobalPinRequest;
use App\Http\Requests\Rooms\UpdateRaisedHandRequest;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use App\Support\GuestIdentity;
use Illuminate\Http\JsonResponse;

class RoomApplicationStateController extends Controller
{
    public function updateHand(
        UpdateRaisedHandRequest $request,
        Room $room,
        GuestIdentity $guestIdentity,
        UpdateRaisedHandAction $updateHand,
    ): JsonResponse {
        $user = $request->user();
        $member = $updateHand->handle(
            $room,
            $user instanceof User ? $user : null,
            $user instanceof User ? null : $guestIdentity->current($request),
            $request->handData(),
        );

        return response()->json(['member' => $this->memberPayload($member)]);
    }

    public function updateGlobalPin(
        UpdateGlobalPinRequest $request,
        Room $room,
        GuestIdentity $guestIdentity,
        UpdateGlobalPinAction $updateGlobalPin,
    ): JsonResponse {
        $user = $request->user();
        $member = $updateGlobalPin->handle(
            $room,
            $user instanceof User ? $user : null,
            $user instanceof User ? null : $guestIdentity->current($request),
            $request->pinData(),
        );

        return response()->json([
            'global_pinned_room_member_id' => $member?->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPayload(RoomMember $member): array
    {
        return [
            'id' => $member->id,
            'room_public_id' => $member->room->public_id,
            'room_subroom_id' => $member->room_subroom_id,
            'display_name' => $member->display_name,
            'role' => $member->role->value,
            'livekit_identity' => $member->livekit_identity,
            'guest' => $member->user_id === null,
            'can_publish_audio' => $member->can_publish_audio,
            'can_publish_video' => $member->can_publish_video,
            'can_publish_screen' => $member->can_publish_screen,
            'hand_raised_at' => $member->hand_raised_at?->toISOString(),
            'left_at' => $member->left_at?->toISOString(),
        ];
    }
}
