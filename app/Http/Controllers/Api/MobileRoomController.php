<?php

namespace App\Http\Controllers\Api;

use App\Actions\Rooms\JoinRoomAction;
use App\Actions\Rooms\RequestWaitingRoomEntryAction;
use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Enums\WaitingRoomStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\JoinMobileRoomRequest;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\WaitingRoomMember;
use App\Services\LiveKit\LiveKitToken;
use App\Services\LiveKit\LiveKitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MobileRoomController extends Controller
{
    public function show(Room $room): JsonResponse
    {
        $this->ensureAccessibleRoom($room);

        return response()->json([
            'room' => $this->roomPayload($room),
        ]);
    }

    public function join(
        JoinMobileRoomRequest $request,
        Room $room,
        JoinRoomAction $joinRoom,
        RequestWaitingRoomEntryAction $requestWaitingRoomEntry,
        LiveKitTokenService $liveKit,
    ): JsonResponse {
        $this->ensureAccessibleRoom($room);

        $guestId = 'guest_mobile_'.Str::random(40);
        $joinData = [
            'display_name' => $request->displayName(),
            'password' => $request->password(),
        ];

        if ($room->waiting_room_enabled) {
            $entry = $requestWaitingRoomEntry->handle($room, null, $guestId, $joinData);

            return response()->json([
                'room' => $this->roomPayload($room),
                'waiting_room' => $this->waitingRoomPayload($entry),
                'waiting_token' => $guestId,
            ], 202);
        }

        $member = $joinRoom->handle($room, null, $guestId, $joinData);
        $token = $liveKit->issueToken($room, $member);

        return response()->json([
            'room' => $this->roomPayload($room),
            'member' => $this->memberPayload($member),
            'livekit' => $this->tokenPayload($token),
        ], 201);
    }

    public function waitingStatus(
        Room $room,
        string $waitingToken,
        LiveKitTokenService $liveKit,
    ): JsonResponse {
        $this->ensureAccessibleRoom($room);

        abort_unless(Str::startsWith($waitingToken, 'guest_mobile_'), 404);

        $entry = WaitingRoomMember::query()
            ->where('room_id', $room->id)
            ->where('guest_id', $waitingToken)
            ->latest('id')
            ->firstOrFail();

        if ($entry->status === WaitingRoomStatus::Pending) {
            return response()->json([
                'room' => $this->roomPayload($room),
                'waiting_room' => $this->waitingRoomPayload($entry),
            ]);
        }

        if ($entry->status === WaitingRoomStatus::Rejected) {
            return response()->json([
                'room' => $this->roomPayload($room),
                'waiting_room' => $this->waitingRoomPayload($entry),
            ]);
        }

        $member = RoomMember::query()
            ->where('room_id', $room->id)
            ->where('guest_id', $waitingToken)
            ->whereNull('left_at')
            ->firstOrFail();
        $token = $liveKit->issueToken($room, $member);

        return response()->json([
            'room' => $this->roomPayload($room),
            'waiting_room' => $this->waitingRoomPayload($entry),
            'member' => $this->memberPayload($member),
            'livekit' => $this->tokenPayload($token),
        ]);
    }

    private function ensureAccessibleRoom(Room $room): void
    {
        abort_unless($room->status === RoomStatus::Active, 404);

        abort_unless(
            $room->visibility === RoomVisibility::Public || $room->password_hash !== null,
            404,
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
    private function waitingRoomPayload(WaitingRoomMember $entry): array
    {
        return [
            'id' => $entry->id,
            'display_name' => $entry->display_name,
            'status' => $entry->status->value,
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
