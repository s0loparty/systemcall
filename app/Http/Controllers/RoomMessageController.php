<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\SendRoomMessageAction;
use App\Http\Requests\Rooms\StoreRoomMessageRequest;
use App\Models\Room;
use App\Models\RoomMessage;
use App\Models\User;
use App\Support\GuestIdentity;
use Illuminate\Http\JsonResponse;

class RoomMessageController extends Controller
{
    public function store(
        StoreRoomMessageRequest $request,
        Room $room,
        GuestIdentity $guestIdentity,
        SendRoomMessageAction $sendMessage,
    ): JsonResponse {
        $user = $request->user();
        $message = $sendMessage->handle(
            $room,
            $user instanceof User ? $user : null,
            $user instanceof User ? null : $guestIdentity->current($request),
            $request->messageData(),
        );

        return response()->json(['message' => $this->messagePayload($message)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function messagePayload(RoomMessage $message): array
    {
        return [
            'id' => $message->id,
            'room_public_id' => $message->room->public_id,
            'room_member_id' => $message->room_member_id,
            'display_name' => $message->room_member_id === null
                ? 'System'
                : $message->member->display_name,
            'body' => $message->body,
            'message_type' => $message->type,
            'created_at' => $message->created_at?->toISOString(),
        ];
    }
}
