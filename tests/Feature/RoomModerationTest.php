<?php

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
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config()->set('services.livekit.url', 'https://livekit.test');
    config()->set('services.livekit.api_key', 'test-key');
    config()->set('services.livekit.api_secret', 'test-secret-test-secret-test-secret');
    config()->set('services.livekit.token_ttl', 600);
});

test('room owners can promote and demote moderators', function () {
    [$room, $owner, , $participant] = moderationRoom();

    Event::fake();

    $this->actingAs($owner)
        ->patchJson(route('rooms.members.role.update', [$room, $participant]), [
            'role' => RoomRole::Moderator->value,
        ])
        ->assertOk()
        ->assertJsonPath('member.role', RoomRole::Moderator->value);

    expect($participant->fresh()->role)->toBe(RoomRole::Moderator);
    Event::assertDispatched(RoomMemberRoleChanged::class);

    $this->actingAs($owner)
        ->patchJson(route('rooms.members.role.update', [$room, $participant]), [
            'role' => RoomRole::Participant->value,
        ])
        ->assertOk()
        ->assertJsonPath('member.role', RoomRole::Participant->value);

    expect($participant->fresh()->role)->toBe(RoomRole::Participant);
});

test('moderators cannot change roles or moderate protected members', function () {
    [$room, , $moderator, $participant, $moderatorUser] = moderationRoom();

    $this->actingAs($moderatorUser)
        ->patchJson(route('rooms.members.role.update', [$room, $participant]), [
            'role' => RoomRole::Moderator->value,
        ])
        ->assertForbidden();

    $this->actingAs($moderatorUser)
        ->postJson(route('rooms.members.mute', [$room, $moderator]))
        ->assertForbidden();

    expect($participant->fresh()->role)->toBe(RoomRole::Participant)
        ->and($moderator->fresh()->can_publish_audio)->toBeTrue();
});

test('moderators can force mute participants without silently unmuting them', function () {
    [$room, , , $participant, $moderatorUser] = moderationRoom();
    $liveKit = fakeLiveKitRoomService();

    Event::fake();

    $this->actingAs($moderatorUser)
        ->postJson(route('rooms.members.mute', [$room, $participant]))
        ->assertOk()
        ->assertJsonPath('member.can_publish_audio', false);

    $participant->refresh();

    expect($participant->can_publish_audio)->toBeFalse()
        ->and($liveKit->permissionUpdates)->toBe([$participant->livekit_identity]);

    Event::assertDispatched(RoomMemberMuted::class);

    $response = $this->actingAs($participant->user)
        ->postJson(route('rooms.livekit_token.store', $room));

    $response->assertOk();

    $payload = (array) JWT::decode(
        $response->json('livekit.token'),
        new Key('test-secret-test-secret-test-secret', 'HS256'),
    );

    $videoGrant = (array) $payload['video'];

    expect($videoGrant['canPublishSources'])->not->toContain('microphone');
});

test('mute all affects active participants but keeps moderators protected', function () {
    [$room, $owner, $moderator, $participant, , $ownerMember] = moderationRoom();
    $secondParticipant = RoomMember::factory()
        ->for($room)
        ->for(User::factory()->create())
        ->create();
    $liveKit = fakeLiveKitRoomService();

    $this->actingAs($owner)
        ->postJson(route('rooms.members.mute_all', $room))
        ->assertOk()
        ->assertJsonCount(2, 'members');

    expect($participant->fresh()->can_publish_audio)->toBeFalse()
        ->and($secondParticipant->fresh()->can_publish_audio)->toBeFalse()
        ->and($ownerMember->fresh()->can_publish_audio)->toBeTrue()
        ->and($moderator->fresh()->can_publish_audio)->toBeTrue()
        ->and($liveKit->permissionUpdates)->toContain($participant->livekit_identity, $secondParticipant->livekit_identity)
        ->and($liveKit->permissionUpdates)->not->toContain($ownerMember->livekit_identity, $moderator->livekit_identity);
});

test('moderators can kick active participants from the room and LiveKit', function () {
    [$room, , , $participant, $moderatorUser] = moderationRoom();
    $liveKit = fakeLiveKitRoomService();

    Event::fake();

    $this->actingAs($moderatorUser)
        ->deleteJson(route('rooms.members.kick', [$room, $participant]))
        ->assertOk()
        ->assertJsonPath('member.id', $participant->id);

    expect($participant->fresh()->left_at)->not->toBeNull()
        ->and($liveKit->removedParticipants)->toBe([$participant->livekit_identity]);

    Event::assertDispatched(RoomMemberRemoved::class);

    $this->actingAs($participant->user)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();
});

test('moderators can ban active participants and block rejoin', function () {
    [$room, , , $participant, $moderatorUser] = moderationRoom();
    $liveKit = fakeLiveKitRoomService();

    Event::fake();

    $this->actingAs($moderatorUser)
        ->postJson(route('rooms.members.ban', [$room, $participant]), [
            'reason' => 'spam',
        ])
        ->assertCreated()
        ->assertJsonPath('ban.reason', 'spam');

    $ban = RoomBan::whereBelongsTo($room)->whereBelongsTo($participant->user)->firstOrFail();

    expect($participant->fresh()->left_at)->not->toBeNull()
        ->and($ban->banned_by_user_id)->toBe($moderatorUser->id)
        ->and($liveKit->removedParticipants)->toBe([$participant->livekit_identity]);

    Event::assertDispatched(RoomMemberBanned::class);

    $this->actingAs($participant->user)
        ->postJson(route('rooms.memberships.store', $room), [
            'display_name' => 'Blocked User',
        ])
        ->assertForbidden();

    $this->actingAs($participant->user)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();
});

test('only room owners can delete rooms', function () {
    [$room, $owner, , , $moderatorUser] = moderationRoom();

    $this->actingAs($moderatorUser)
        ->deleteJson(route('rooms.destroy', $room))
        ->assertForbidden();

    $this->actingAs($owner)
        ->deleteJson(route('rooms.destroy', $room))
        ->assertNoContent();

    $this->assertModelMissing($room);
});

/**
 * @return array{0:Room,1:User,2:RoomMember,3:RoomMember,4:User,5:RoomMember}
 */
function moderationRoom(): array
{
    $owner = User::factory()->create();
    $moderatorUser = User::factory()->create();
    $participantUser = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    $ownerMember = RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->owner()
        ->create();
    $moderator = RoomMember::factory()
        ->for($room)
        ->for($moderatorUser)
        ->moderator()
        ->create();
    $participant = RoomMember::factory()
        ->for($room)
        ->for($participantUser)
        ->create();

    return [$room, $owner, $moderator, $participant, $moderatorUser, $ownerMember];
}

function fakeLiveKitRoomService(): LiveKitRoomService
{
    $fake = new class extends LiveKitRoomService
    {
        /** @var list<string> */
        public array $removedParticipants = [];

        /** @var list<string> */
        public array $permissionUpdates = [];

        public function removeParticipant(Room $room, RoomMember $member): void
        {
            $this->removedParticipants[] = $member->livekit_identity;
        }

        public function updateParticipantPermissions(Room $room, RoomMember $member): void
        {
            $this->permissionUpdates[] = $member->livekit_identity;
        }
    };

    app()->instance(LiveKitRoomService::class, $fake);

    return $fake;
}
