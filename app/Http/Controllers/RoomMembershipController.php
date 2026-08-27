<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\JoinRoomAction;
use App\Actions\Rooms\RequestWaitingRoomEntryAction;
use App\Events\Rooms\RoomMemberLeft;
use App\Http\Requests\Rooms\JoinRoomRequest;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use App\Models\WaitingRoomMember;
use App\Support\GuestIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoomMembershipController extends Controller
{
    public function store(
        JoinRoomRequest $request,
        Room $room,
        JoinRoomAction $joinRoom,
        RequestWaitingRoomEntryAction $requestWaitingRoomEntry,
        GuestIdentity $guestIdentity,
    ): JsonResponse {
        $user = $request->user();
        $guestId = $user === null ? $guestIdentity->getOrCreate($request) : null;

        if ($room->waiting_room_enabled) {
            $member = $this->activeMember($room, $user, $guestId);

            if ($member instanceof RoomMember) {
                return response()->json(['member' => $this->memberPayload($member)]);
            }

            $entry = $requestWaitingRoomEntry->handle($room, $user, $guestId, $request->joinData());

            return response()->json(['waiting_room' => $this->waitingRoomPayload($entry)], 202);
        }

        $member = $joinRoom->handle($room, $user, $guestId, $request->joinData());

        return response()->json(['member' => $this->memberPayload($member)], 201);
    }

    public function destroy(
        Request $request,
        Room $room,
        GuestIdentity $guestIdentity,
    ): Response {
        $user = $request->user();
        $guestId = $user === null ? $guestIdentity->current($request) : null;
        $member = $this->activeMember($room, $user, $guestId);

        if (! $member instanceof RoomMember) {
            return response()->noContent();
        }

        $member->forceFill([
            'hand_raised_at' => null,
            'left_at' => now(),
        ])->save();
        $member->refresh();

        if ($room->global_pinned_room_member_id === $member->id) {
            $room->forceFill(['global_pinned_room_member_id' => null])->save();
        }

        RoomMemberLeft::dispatch($member);

        return response()->noContent();
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
    private function waitingRoomPayload(WaitingRoomMember $entry): array
    {
        return [
            'id' => $entry->id,
            'room_public_id' => $entry->room->public_id,
            'display_name' => $entry->display_name,
            'status' => $entry->status->value,
            'guest' => $entry->user_id === null,
        ];
    }

    private function activeMember(Room $room, ?User $user, ?string $guestId): ?RoomMember
    {
        $query = $room->members()->whereNull('left_at');

        if ($user instanceof User) {
            return $query->whereBelongsTo($user)->first();
        }

        return $guestId === null
            ? null
            : $query->where('guest_id', $guestId)->first();
    }
}
