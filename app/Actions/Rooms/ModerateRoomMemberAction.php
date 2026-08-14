<?php

namespace App\Actions\Rooms;

use App\Enums\RoomRole;
use App\Events\Rooms\RoomMemberBanned;
use App\Events\Rooms\RoomMemberMuted;
use App\Events\Rooms\RoomMemberRemoved;
use App\Events\Rooms\RoomMemberRoleChanged;
use App\Models\Room;
use App\Models\RoomBan;
use App\Models\RoomMember;
use App\Models\User;
use App\Services\LiveKit\LiveKitRoomService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ModerateRoomMemberAction
{
    public function __construct(private LiveKitRoomService $liveKit) {}

    public function changeRole(Room $room, RoomMember $target, User $actor, RoomRole $role): RoomMember
    {
        $actorMember = $this->actorMember($room, $actor);

        if ($actorMember->role !== RoomRole::Owner) {
            throw new AuthorizationException('Only the room owner may change roles.');
        }

        if ($target->room_id !== $room->id || $target->id === $actorMember->id || $target->role === RoomRole::Owner) {
            throw new AuthorizationException('This member role cannot be changed.');
        }

        if ($role === RoomRole::Owner) {
            throw new AuthorizationException('Room ownership transfer is not supported yet.');
        }

        $target->forceFill(['role' => $role])->save();
        $target->refresh();

        RoomMemberRoleChanged::dispatch($target, $actor);

        return $target;
    }

    public function mute(Room $room, RoomMember $target, User $actor): RoomMember
    {
        $this->authorizeCanModerateTarget($room, $target, $actor);

        $target->forceFill(['can_publish_audio' => false])->save();
        $target->refresh();

        $this->liveKit->updateParticipantPermissions($room, $target);
        RoomMemberMuted::dispatch($target, $actor);

        return $target;
    }

    /**
     * @return array<int, RoomMember>
     */
    public function muteAll(Room $room, User $actor): array
    {
        $actorMember = $this->actorMember($room, $actor);
        $muted = [];

        $room->members()
            ->whereNull('left_at')
            ->get()
            ->each(function (RoomMember $target) use ($room, $actor, $actorMember, &$muted): void {
                if ($target->id === $actorMember->id || $target->isModerator()) {
                    return;
                }

                $target->forceFill(['can_publish_audio' => false])->save();
                $target->refresh();
                $this->liveKit->updateParticipantPermissions($room, $target);
                RoomMemberMuted::dispatch($target, $actor);
                $muted[] = $target;
            });

        return $muted;
    }

    public function kick(Room $room, RoomMember $target, User $actor): RoomMember
    {
        $this->authorizeCanModerateTarget($room, $target, $actor);

        $target->forceFill(['left_at' => now()])->save();
        $target->refresh();

        $this->liveKit->removeParticipant($room, $target);
        RoomMemberRemoved::dispatch($target, $actor);

        return $target;
    }

    /**
     * @param  array{reason?:string|null,expires_at?:string|null}  $data
     */
    public function ban(Room $room, RoomMember $target, User $actor, array $data): RoomBan
    {
        $this->authorizeCanModerateTarget($room, $target, $actor);

        return DB::transaction(function () use ($room, $target, $actor, $data): RoomBan {
            $ban = RoomBan::query()->create([
                'room_id' => $room->id,
                'user_id' => $target->user_id,
                'guest_id' => $target->guest_id,
                'banned_by_user_id' => $actor->id,
                'reason' => $data['reason'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            $target->forceFill(['left_at' => now()])->save();
            $target->refresh();

            $this->liveKit->removeParticipant($room, $target);
            RoomMemberBanned::dispatch($target, $ban, $actor);

            return $ban;
        });
    }

    private function authorizeCanModerateTarget(Room $room, RoomMember $target, User $actor): void
    {
        $actorMember = $this->actorMember($room, $actor);

        if ($target->room_id !== $room->id || $target->left_at !== null) {
            throw new AuthorizationException('This member is not active in the room.');
        }

        if ($target->id === $actorMember->id) {
            throw new AuthorizationException('You cannot moderate yourself.');
        }

        if ($actorMember->role === RoomRole::Participant || ($actorMember->role === RoomRole::Moderator && $target->isModerator())) {
            throw new AuthorizationException('You cannot moderate this member.');
        }
    }

    private function actorMember(Room $room, User $actor): RoomMember
    {
        $member = $room->members()
            ->whereBelongsTo($actor)
            ->whereNull('left_at')
            ->first();

        if (! $member instanceof RoomMember || ! $member->isModerator()) {
            throw new AuthorizationException('Only room moderators may perform this action.');
        }

        return $member;
    }
}
