<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\SwitchRoomSubroomAction;
use App\Http\Requests\Rooms\SwitchRoomSubroomRequest;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomSubroom;
use App\Models\User;
use App\Services\LiveKit\LiveKitToken;
use App\Services\LiveKit\LiveKitTokenService;
use App\Support\GuestIdentity;
use Illuminate\Http\JsonResponse;

class RoomSubroomTokenController extends Controller
{
    public function store(
        SwitchRoomSubroomRequest $request,
        Room $room,
        GuestIdentity $guestIdentity,
        SwitchRoomSubroomAction $switchSubroom,
        LiveKitTokenService $liveKit,
    ): JsonResponse {
        $user = $request->user();
        $switch = $switchSubroom->handle(
            $room,
            $user instanceof User ? $user : null,
            $user instanceof User ? null : $guestIdentity->current($request),
            $request->switchData(),
        );
        $token = $liveKit->issueToken($room, $switch['member'], $switch['subroom']);

        return response()->json([
            'livekit' => $this->tokenPayload($token),
            'member' => $this->memberPayload($switch['member']),
            'subroom' => $switch['subroom'] instanceof RoomSubroom
                ? $this->subroomPayload($switch['subroom'])
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenPayload(LiveKitToken $token): array
    {
        return [
            'url' => $token->url,
            'token' => $token->token,
            'room_name' => $token->roomName,
            'identity' => $token->identity,
            'expires_in' => $token->expiresIn,
        ];
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

    /**
     * @return array<string, mixed>
     */
    private function subroomPayload(RoomSubroom $subroom): array
    {
        return [
            'id' => $subroom->id,
            'room_public_id' => $subroom->room->public_id,
            'public_id' => $subroom->public_id,
            'name' => $subroom->name,
            'position' => $subroom->position,
            'is_default' => $subroom->is_default,
        ];
    }
}
