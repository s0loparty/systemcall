<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\ModerateRoomMemberAction;
use App\Enums\RoomRole;
use App\Http\Requests\Rooms\BanRoomMemberRequest;
use App\Http\Requests\Rooms\MoveRoomMemberSubroomRequest;
use App\Models\Room;
use App\Models\RoomBan;
use App\Models\RoomMember;
use App\Models\RoomSubroom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomModerationController extends Controller
{
    public function updateRole(
        Request $request,
        Room $room,
        RoomMember $roomMember,
        ModerateRoomMemberAction $moderate,
    ): JsonResponse {
        $data = $request->validate([
            'role' => ['required', Rule::in([RoomRole::Moderator->value, RoomRole::Participant->value])],
        ]);

        $member = $moderate->changeRole($room, $roomMember, $this->actor($request), RoomRole::from((string) $data['role']));

        return response()->json(['member' => $this->memberPayload($member)]);
    }

    public function mute(
        Request $request,
        Room $room,
        RoomMember $roomMember,
        ModerateRoomMemberAction $moderate,
    ): JsonResponse {
        $member = $moderate->mute($room, $roomMember, $this->actor($request));

        return response()->json(['member' => $this->memberPayload($member)]);
    }

    public function muteAll(Request $request, Room $room, ModerateRoomMemberAction $moderate): JsonResponse
    {
        $members = $moderate->muteAll($room, $this->actor($request));

        return response()->json([
            'members' => array_map(fn (RoomMember $member): array => $this->memberPayload($member), $members),
        ]);
    }

    public function requestMedia(
        Request $request,
        Room $room,
        RoomMember $roomMember,
        ModerateRoomMemberAction $moderate,
    ): JsonResponse {
        $data = $this->validateMediaKind($request);
        $member = $moderate->requestMedia($room, $roomMember, $this->actor($request), $data['media_kind']);

        return response()->json(['member' => $this->memberPayload($member)]);
    }

    public function disableMedia(
        Request $request,
        Room $room,
        RoomMember $roomMember,
        ModerateRoomMemberAction $moderate,
    ): JsonResponse {
        $data = $this->validateMediaKind($request);
        $member = $moderate->disableMedia($room, $roomMember, $this->actor($request), $data['media_kind']);

        return response()->json(['member' => $this->memberPayload($member)]);
    }

    public function disableMediaAll(Request $request, Room $room, ModerateRoomMemberAction $moderate): JsonResponse
    {
        $data = $this->validateMediaKind($request);
        $members = $moderate->disableMediaAll($room, $this->actor($request), $data['media_kind']);

        return response()->json([
            'members' => array_map(fn (RoomMember $member): array => $this->memberPayload($member), $members),
        ]);
    }

    public function kick(
        Request $request,
        Room $room,
        RoomMember $roomMember,
        ModerateRoomMemberAction $moderate,
    ): JsonResponse {
        $member = $moderate->kick($room, $roomMember, $this->actor($request));

        return response()->json(['member' => $this->memberPayload($member)]);
    }

    public function ban(
        BanRoomMemberRequest $request,
        Room $room,
        RoomMember $roomMember,
        ModerateRoomMemberAction $moderate,
    ): JsonResponse {
        $ban = $moderate->ban($room, $roomMember, $this->actor($request), $request->banData());

        return response()->json(['ban' => $this->banPayload($ban)], 201);
    }

    public function moveToSubroom(
        MoveRoomMemberSubroomRequest $request,
        Room $room,
        RoomMember $roomMember,
        ModerateRoomMemberAction $moderate,
    ): JsonResponse {
        $move = $moderate->moveToSubroom($room, $roomMember, $this->actor($request), $request->moveData());

        return response()->json([
            'member' => $this->memberPayload($move['member']),
            'subroom' => $move['subroom'] instanceof RoomSubroom
                ? $this->subroomPayload($move['subroom'])
                : null,
        ]);
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

    private function actor(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return array{media_kind:string}
     */
    private function validateMediaKind(Request $request): array
    {
        return $request->validate([
            'media_kind' => ['required', Rule::in(['microphone', 'camera'])],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function banPayload(RoomBan $ban): array
    {
        return [
            'id' => $ban->id,
            'room_public_id' => $ban->room->public_id,
            'user_id' => $ban->user_id,
            'guest_id' => $ban->guest_id,
            'reason' => $ban->reason,
            'expires_at' => $ban->expires_at?->toISOString(),
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
