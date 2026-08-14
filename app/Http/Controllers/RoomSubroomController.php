<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\CreateRoomSubroomAction;
use App\Http\Requests\Rooms\StoreRoomSubroomRequest;
use App\Models\Room;
use App\Models\RoomSubroom;
use App\Models\User;
use App\Support\GuestIdentity;
use Illuminate\Http\JsonResponse;

class RoomSubroomController extends Controller
{
    public function store(
        StoreRoomSubroomRequest $request,
        Room $room,
        GuestIdentity $guestIdentity,
        CreateRoomSubroomAction $createSubroom,
    ): JsonResponse {
        $user = $request->user();
        $subroom = $createSubroom->handle(
            $room,
            $user instanceof User ? $user : null,
            $user instanceof User ? null : $guestIdentity->current($request),
            $request->subroomData(),
        );

        return response()->json(['subroom' => $this->subroomPayload($subroom)], 201);
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
