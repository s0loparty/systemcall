<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Models\Room;
use App\Models\User;
use App\Services\LiveKit\LiveKitTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoomCallController extends Controller
{
    public function show(Request $request, Room $room, LiveKitTokenService $liveKit): Response
    {
        abort_unless($this->canOpen($request, $room), 403);

        return Inertia::render('rooms/Call', [
            'room' => [
                'public_id' => $room->public_id,
                'name' => $room->name,
                'description' => $room->description,
                'visibility' => $room->visibility->value,
                'has_password' => $room->password_hash !== null,
                'waiting_room_enabled' => $room->waiting_room_enabled,
                'livekit_room_name' => $liveKit->roomName($room),
            ],
            'can_join_without_password' => $this->canJoinWithoutPassword($request, $room),
        ]);
    }

    private function canOpen(Request $request, Room $room): bool
    {
        if ($room->visibility === RoomVisibility::Public && $room->status === RoomStatus::Active) {
            return true;
        }

        if ($room->password_hash !== null && $room->status === RoomStatus::Active) {
            return true;
        }

        $user = $request->user();

        return $user instanceof User && Gate::forUser($user)->allows('view', $room);
    }

    private function canJoinWithoutPassword(Request $request, Room $room): bool
    {
        if ($room->visibility === RoomVisibility::Public || $room->password_hash === null) {
            return true;
        }

        $user = $request->user();

        return $user instanceof User && $room->members()
            ->whereBelongsTo($user)
            ->whereNull('left_at')
            ->exists();
    }
}
