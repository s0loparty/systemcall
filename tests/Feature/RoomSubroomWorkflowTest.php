<?php

use App\Enums\RoomRole;
use App\Events\Rooms\RoomMemberSubroomChanged;
use App\Events\Rooms\RoomSubroomCreated;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomSubroom;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('services.livekit.url', 'https://livekit.test');
    config()->set('services.livekit.api_key', 'test-key');
    config()->set('services.livekit.api_secret', 'test-secret-test-secret-test-secret');
    config()->set('services.livekit.token_ttl', 600);
});

test('moderators can create up to five subrooms', function () {
    [$room, $owner] = subroomWorkflowRoom();

    Event::fake([RoomSubroomCreated::class]);

    $this->actingAs($owner)
        ->postJson(route('rooms.subrooms.store', $room), [
            'name' => 'Breakout A',
        ])
        ->assertCreated()
        ->assertJsonPath('subroom.name', 'Breakout A')
        ->assertJsonPath('subroom.position', 1);

    $subroom = RoomSubroom::whereBelongsTo($room)->firstOrFail();

    expect($subroom->public_id)->toHaveLength(16)
        ->and($subroom->livekit_room_name)->toBe('room-'.$room->public_id.'-subroom-'.$subroom->public_id);

    Event::assertDispatched(RoomSubroomCreated::class, function (RoomSubroomCreated $event) use ($room, $subroom): bool {
        $payload = $event->broadcastWith();

        expect($event->broadcastAs())->toBe('room.subroom.created')
            ->and($event->broadcastOn()[0]->name)->toBe('private-room.'.$room->public_id)
            ->and($payload['subroom']['id'])->toBe($subroom->id);

        return true;
    });

    RoomSubroom::factory()
        ->count(4)
        ->for($room)
        ->sequence(
            ['name' => 'Breakout B', 'position' => 2],
            ['name' => 'Breakout C', 'position' => 3],
            ['name' => 'Breakout D', 'position' => 4],
            ['name' => 'Breakout E', 'position' => 5],
        )
        ->create();

    $this->actingAs($owner)
        ->postJson(route('rooms.subrooms.store', $room), [
            'name' => 'Overflow',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('ordinary participants cannot create subrooms', function () {
    [$room, , , $participantUser] = subroomWorkflowRoom();

    $this->actingAs($participantUser)
        ->postJson(route('rooms.subrooms.store', $room), [
            'name' => 'Participants only',
        ])
        ->assertForbidden();
});

test('subroom token switches member into a separate LiveKit room', function () {
    [$room, , , $participantUser, $participant] = subroomWorkflowRoom();
    $subroom = RoomSubroom::factory()
        ->for($room)
        ->create([
            'name' => 'Workshop',
            'livekit_room_name' => 'subroom-workshop',
        ]);

    Event::fake([RoomMemberSubroomChanged::class]);

    $response = $this->actingAs($participantUser)
        ->postJson(route('rooms.subroom_token.store', $room), [
            'room_subroom_public_id' => $subroom->public_id,
        ]);

    $response->assertOk()
        ->assertJsonPath('livekit.url', 'https://livekit.test')
        ->assertJsonPath('livekit.room_name', 'subroom-workshop')
        ->assertJsonPath('livekit.identity', $participant->livekit_identity)
        ->assertJsonPath('member.room_subroom_id', $subroom->id)
        ->assertJsonPath('subroom.public_id', $subroom->public_id);

    $payload = (array) JWT::decode(
        $response->json('livekit.token'),
        new Key('test-secret-test-secret-test-secret', 'HS256'),
    );
    $videoGrant = (array) $payload['video'];
    $metadata = json_decode((string) $payload['metadata'], true, 512, JSON_THROW_ON_ERROR);

    expect($participant->fresh()->room_subroom_id)->toBe($subroom->id)
        ->and($videoGrant['room'])->toBe('subroom-workshop')
        ->and($metadata['room_subroom_id'])->toBe($subroom->id)
        ->and($metadata['room_subroom_public_id'])->toBe($subroom->public_id);

    Event::assertDispatched(RoomMemberSubroomChanged::class, function (RoomMemberSubroomChanged $event) use ($room, $participant, $subroom): bool {
        $payload = $event->broadcastWith();

        expect($event->broadcastAs())->toBe('room.member.subroom-changed')
            ->and($event->broadcastOn()[0]->name)->toBe('private-room.'.$room->public_id)
            ->and($payload['member']['id'])->toBe($participant->id)
            ->and($payload['subroom']['id'])->toBe($subroom->id);

        return true;
    });
});

test('subroom token can switch member back to the main LiveKit room', function () {
    [$room, , , $participantUser, $participant] = subroomWorkflowRoom();
    $subroom = RoomSubroom::factory()
        ->for($room)
        ->create();
    $participant->forceFill(['room_subroom_id' => $subroom->id])->save();

    $response = $this->actingAs($participantUser)
        ->postJson(route('rooms.subroom_token.store', $room), [
            'room_subroom_public_id' => null,
        ]);

    $response->assertOk()
        ->assertJsonPath('livekit.room_name', 'room-'.$room->public_id)
        ->assertJsonPath('member.room_subroom_id', null)
        ->assertJsonPath('subroom', null);

    $payload = (array) JWT::decode(
        $response->json('livekit.token'),
        new Key('test-secret-test-secret-test-secret', 'HS256'),
    );
    $videoGrant = (array) $payload['video'];
    $metadata = json_decode((string) $payload['metadata'], true, 512, JSON_THROW_ON_ERROR);

    expect($participant->fresh()->room_subroom_id)->toBeNull()
        ->and($videoGrant['room'])->toBe('room-'.$room->public_id)
        ->and($metadata['room_subroom_id'])->toBeNull()
        ->and($metadata['room_subroom_public_id'])->toBeNull();
});

test('subroom switching rejects foreign targets and non members', function () {
    [$room, , , $participantUser] = subroomWorkflowRoom();
    $foreignSubroom = RoomSubroom::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($participantUser)
        ->postJson(route('rooms.subroom_token.store', $room), [
            'room_subroom_public_id' => $foreignSubroom->public_id,
        ])
        ->assertForbidden();

    $this->actingAs($stranger)
        ->postJson(route('rooms.subroom_token.store', $room), [
            'room_subroom_public_id' => null,
        ])
        ->assertForbidden();
});

test('call page exposes subrooms only to active room members', function () {
    [$room, $owner] = subroomWorkflowRoom();
    $stranger = User::factory()->create();

    $subroom = RoomSubroom::factory()
        ->for($room)
        ->create([
            'name' => 'Workshop',
            'position' => 1,
        ]);

    $this->actingAs($stranger)
        ->get(route('rooms.call.show', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Call')
            ->where('room_subrooms', []));

    $this->actingAs($owner)
        ->get(route('rooms.call.show', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Call')
            ->where('room_subrooms.0.id', $subroom->id)
            ->where('room_subrooms.0.name', 'Workshop'));
});

/**
 * @return array{0:Room,1:User,2:RoomMember,3:User,4:RoomMember}
 */
function subroomWorkflowRoom(): array
{
    $owner = User::factory()->create();
    $participantUser = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    $ownerMember = RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->create([
            'role' => RoomRole::Owner,
        ]);
    $participant = RoomMember::factory()
        ->for($room)
        ->for($participantUser)
        ->create();

    return [$room, $owner, $ownerMember, $participantUser, $participant];
}
