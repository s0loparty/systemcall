<?php

namespace App\Http\Controllers;

use App\Enums\WaitingRoomStatus;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use App\Models\WaitingRoomMember;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class WaitingRoomController extends Controller
{
    public function index(Request $request, Room $room): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        abort_unless($this->canModerate($request, $room), 403);

        $entries = $room->waitingMembers()
            ->where('status', WaitingRoomStatus::Pending)
            ->latest()
            ->get()
            ->map(fn (WaitingRoomMember $entry): array => $this->entryPayload($entry))
            ->all();

        return response()->json(['waiting_room' => $entries]);
    }

    /**
     * @return array<string, mixed>
     */
    private function entryPayload(WaitingRoomMember $entry): array
    {
        return [
            'id' => $entry->id,
            'room_public_id' => $entry->room->public_id,
            'display_name' => $entry->display_name,
            'status' => $entry->status->value,
            'guest' => $entry->user_id === null,
            'created_at' => $entry->created_at?->toISOString(),
        ];
    }

    private function canModerate(Request $request, Room $room): bool
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return false;
        }

        return $room->members()
            ->whereBelongsTo($user)
            ->whereNull('left_at')
            ->get()
            ->contains(fn (RoomMember $member): bool => $member->isModerator());
    }
}
