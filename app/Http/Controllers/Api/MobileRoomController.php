<?php

namespace App\Http\Controllers\Api;

use App\Actions\Rooms\JoinRoomAction;
use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\JoinMobileRoomRequest;
use App\Models\Room;
use App\Models\RoomMember;
use App\Services\LiveKit\LiveKitToken;
use App\Services\LiveKit\LiveKitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MobileRoomController extends Controller
{
    public function show(Room $room): JsonResponse
    {
        $this->ensureSupportedRoom($room);

        return response()->json([
            'room' => $this->roomPayload($room),
        ]);
    }

    public function join(
        JoinMobileRoomRequest $request,
        Room $room,
        JoinRoomAction $joinRoom,
        LiveKitTokenService $liveKit,
    ): JsonResponse {
        $this->ensureSupportedRoom($room);

        $guestId = 'guest_mobile_'.Str::random(40);
        $member = $joinRoom->handle($room, null, $guestId, [
            'display_name' => $request->displayName(),
        ]);
        $token = $liveKit->issueToken($room, $member);

        return response()->json([
            'room' => $this->roomPayload($room),
            'member' => $this->memberPayload($member),
            'livekit' => $this->tokenPayload($token),
        ], 201);
    }

    private function ensureSupportedRoom(Room $room): void
    {
        abort_unless(
            $room->status === RoomStatus::Active && $room->visibility === RoomVisibility::Public,
            404,
        );

        abort_if(
            $room->waiting_room_enabled,
            422,
            'Waiting rooms are not supported by the mobile MVP.',
        );

        abort_if(
            $room->password_hash !== null,
            422,
            'Password-protected rooms are not supported by the mobile MVP.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function roomPayload(Room $room): array
    {
        return [
            'public_id' => $room->public_id,
            'name' => $room->name,
            'description' => $room->description,
            'visibility' => $room->visibility->value,
            'status' => $room->status->value,
            'has_password' => $room->password_hash !== null,
            'waiting_room_enabled' => $room->waiting_room_enabled,
            'max_participants' => $room->max_participants,
            'ended_at' => $room->ended_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPayload(RoomMember $member): array
    {
        return [
            'id' => $member->id,
            'display_name' => $member->display_name,
            'livekit_identity' => $member->livekit_identity,
        ];
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
}
