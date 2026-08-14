<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\FindAuthorizedRoomMemberAction;
use App\Models\Room;
use App\Services\LiveKit\LiveKitToken;
use App\Services\LiveKit\LiveKitTokenService;
use App\Support\GuestIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveKitTokenController extends Controller
{
    public function store(
        Request $request,
        Room $room,
        GuestIdentity $guestIdentity,
        FindAuthorizedRoomMemberAction $findMember,
        LiveKitTokenService $liveKit,
    ): JsonResponse {
        $user = $request->user();
        $guestId = $user === null ? $guestIdentity->current($request) : null;
        $member = $findMember->handle($room, $user, $guestId);
        $token = $liveKit->issueToken($room, $member);

        return response()->json(['livekit' => $this->tokenPayload($token)]);
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
