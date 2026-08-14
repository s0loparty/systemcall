<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\DecideWaitingRoomEntryAction;
use App\Models\Room;
use App\Models\User;
use App\Models\WaitingRoomMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WaitingRoomDecisionController extends Controller
{
    public function approve(
        Request $request,
        Room $room,
        WaitingRoomMember $waitingRoomMember,
        DecideWaitingRoomEntryAction $decideEntry,
    ): JsonResponse {
        $moderator = $this->authorizeEntry($request, $room, $waitingRoomMember);

        $entry = $decideEntry->approve($waitingRoomMember, $moderator);

        return response()->json(['waiting_room' => $this->entryPayload($entry)]);
    }

    public function reject(
        Request $request,
        Room $room,
        WaitingRoomMember $waitingRoomMember,
        DecideWaitingRoomEntryAction $decideEntry,
    ): JsonResponse {
        $moderator = $this->authorizeEntry($request, $room, $waitingRoomMember);

        $entry = $decideEntry->reject($waitingRoomMember, $moderator);

        return response()->json(['waiting_room' => $this->entryPayload($entry)]);
    }

    private function authorizeEntry(Request $request, Room $room, WaitingRoomMember $entry): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless($entry->room_id === $room->id, 404);

        Gate::forUser($user)->authorize('update', $entry);

        return $user;
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
            'decided_at' => $entry->decided_at?->toISOString(),
        ];
    }
}
