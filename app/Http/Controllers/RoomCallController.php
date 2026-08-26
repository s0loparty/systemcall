<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Enums\WaitingRoomStatus;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomMessage;
use App\Models\RoomSubroom;
use App\Models\User;
use App\Models\WaitingRoomMember;
use App\Services\LiveKit\LiveKitTokenService;
use App\Support\GuestIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoomCallController extends Controller
{
    public function prepare(Request $request, Room $room, LiveKitTokenService $liveKit, GuestIdentity $guestIdentity): Response
    {
        return Inertia::render('rooms/Prepare', $this->pageProps($request, $room, $liveKit, $guestIdentity));
    }

    public function waiting(Request $request, Room $room, LiveKitTokenService $liveKit, GuestIdentity $guestIdentity): RedirectResponse|Response
    {
        if ($this->guestNeedsPrepare($request, $room, $guestIdentity)) {
            return to_route('rooms.call.prepare', $room);
        }

        return Inertia::render('rooms/Waiting', $this->pageProps($request, $room, $liveKit, $guestIdentity, true));
    }

    public function show(Request $request, Room $room, LiveKitTokenService $liveKit, GuestIdentity $guestIdentity): RedirectResponse|Response
    {
        if ($this->guestNeedsPrepare($request, $room, $guestIdentity)) {
            return to_route('rooms.call.prepare', $room);
        }

        return Inertia::render('rooms/Call', $this->pageProps($request, $room, $liveKit, $guestIdentity));
    }

    /**
     * @return array<string, mixed>
     */
    private function pageProps(
        Request $request,
        Room $room,
        LiveKitTokenService $liveKit,
        GuestIdentity $guestIdentity,
        bool $includeWaitingRoomEntries = false,
    ): array {
        abort_unless($this->canOpen($request, $room), 403);

        $canModerateWaitingRoom = $this->canModerateWaitingRoom($request, $room);
        $currentRoomMemberId = $this->currentRoomMemberId($request, $room, $guestIdentity);
        $canSeeRoomMembers = $canModerateWaitingRoom || $currentRoomMemberId !== null;
        $canSeeWaitingRoomEntries = $canModerateWaitingRoom || $includeWaitingRoomEntries;

        return [
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
            'can_moderate_waiting_room' => $canModerateWaitingRoom,
            'can_moderate_members' => $canModerateWaitingRoom,
            'current_room_member_id' => $currentRoomMemberId,
            'global_pinned_room_member_id' => $room->global_pinned_room_member_id,
            'room_members' => $canSeeRoomMembers
                ? $this->activeRoomMembers($room)
                : [],
            'room_subrooms' => $canSeeRoomMembers
                ? $this->subrooms($room)
                : [],
            'room_messages' => $currentRoomMemberId !== null ? $this->recentMessages($room) : [],
            'waiting_room_entries' => $canSeeWaitingRoomEntries
                ? $this->pendingWaitingRoomEntries($room)
                : [],
            'current_waiting_room_entry' => $this->currentWaitingRoomEntry($request, $room, $guestIdentity),
            'testing_media_mode' => $this->testingMediaMode($request),
        ];
    }

    private function testingMediaMode(Request $request): ?string
    {
        if (! app()->environment('testing')) {
            return null;
        }

        $mode = $request->query('_e2e_media');

        return in_array($mode, ['fake', 'error'], true) ? $mode : null;
    }

    private function guestNeedsPrepare(Request $request, Room $room, GuestIdentity $guestIdentity): bool
    {
        if ($request->user() instanceof User) {
            return false;
        }

        return $this->currentRoomMemberId($request, $room, $guestIdentity) === null
            && $this->currentWaitingRoomEntry($request, $room, $guestIdentity) === null;
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
        if ($room->password_hash === null) {
            return true;
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($room->owner()->is($user)) {
            return true;
        }

        return $room->members()
            ->whereBelongsTo($user)
            ->whereNull('left_at')
            ->exists();
    }

    private function canModerateWaitingRoom(Request $request, Room $room): bool
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendingWaitingRoomEntries(Room $room): array
    {
        return $room->waitingMembers()
            ->where('status', WaitingRoomStatus::Pending)
            ->latest()
            ->get()
            ->map(fn (WaitingRoomMember $entry): array => $this->waitingRoomEntryPayload($entry))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activeRoomMembers(Room $room): array
    {
        return $room->members()
            ->whereNull('left_at')
            ->latest()
            ->get()
            ->map(fn (RoomMember $member): array => [
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
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function subrooms(Room $room): array
    {
        return $room->subrooms()
            ->orderBy('position')
            ->get()
            ->map(fn (RoomSubroom $subroom): array => [
                'id' => $subroom->id,
                'room_public_id' => $room->public_id,
                'public_id' => $subroom->public_id,
                'name' => $subroom->name,
                'position' => $subroom->position,
                'is_default' => $subroom->is_default,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentMessages(Room $room): array
    {
        return $room->messages()
            ->with('member')
            ->whereNull('deleted_at')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (RoomMessage $message): array => [
                'id' => $message->id,
                'room_public_id' => $room->public_id,
                'room_member_id' => $message->room_member_id,
                'display_name' => $message->room_member_id === null
                    ? 'System'
                    : $message->member->display_name,
                'body' => $message->body,
                'message_type' => $message->type,
                'created_at' => $message->created_at?->toISOString(),
            ])
            ->all();
    }

    private function currentRoomMemberId(Request $request, Room $room, GuestIdentity $guestIdentity): ?int
    {
        $user = $request->user();
        $guestId = $user instanceof User ? null : $guestIdentity->current($request);

        if (! $user instanceof User && $guestId === null) {
            return null;
        }

        $member = $room->members()
            ->whereNull('left_at')
            ->where(function ($query) use ($user, $guestId): void {
                if ($user instanceof User) {
                    $query->whereBelongsTo($user);

                    return;
                }

                $query->where('guest_id', $guestId);
            })
            ->latest()
            ->first();

        return $member instanceof RoomMember ? $member->id : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentWaitingRoomEntry(Request $request, Room $room, GuestIdentity $guestIdentity): ?array
    {
        if (! $room->waiting_room_enabled) {
            return null;
        }

        $user = $request->user();
        $guestId = $user instanceof User ? null : $guestIdentity->current($request);

        if (! $user instanceof User && $guestId === null) {
            return null;
        }

        $entry = $room->waitingMembers()
            ->whereIn('status', [WaitingRoomStatus::Pending, WaitingRoomStatus::Approved, WaitingRoomStatus::Rejected])
            ->where(function ($query) use ($user, $guestId): void {
                if ($user instanceof User) {
                    $query->whereBelongsTo($user);

                    return;
                }

                $query->where('guest_id', $guestId);
            })
            ->latest()
            ->first();

        return $entry instanceof WaitingRoomMember
            ? $this->waitingRoomEntryPayload($entry)
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function waitingRoomEntryPayload(WaitingRoomMember $entry): array
    {
        return [
            'id' => $entry->id,
            'room_public_id' => $entry->room->public_id,
            'display_name' => $entry->display_name,
            'status' => $entry->status->value,
            'guest' => $entry->user_id === null,
            'created_at' => $entry->created_at?->toISOString(),
            'decided_at' => $entry->decided_at?->toISOString(),
        ];
    }
}
