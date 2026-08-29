<?php

use App\Enums\WaitingRoomStatus;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\WaitingRoomMember;
use App\Services\LiveKit\LiveKitToken;
use App\Services\LiveKit\LiveKitTokenService;
use Illuminate\Support\Facades\Hash;

it('returns an active public room for the mobile client', function () {
    $room = Room::factory()->public()->create([
        'name' => 'Mobile test room',
        'waiting_room_enabled' => false,
        'password_hash' => null,
    ]);

    $this->getJson("/api/mobile/rooms/{$room->public_id}")
        ->assertOk()
        ->assertJsonPath('room.public_id', $room->public_id)
        ->assertJsonPath('room.name', 'Mobile test room')
        ->assertJsonPath('room.has_password', false)
        ->assertJsonPath('room.waiting_room_enabled', false);
});

it('does not expose unsupported rooms to the mobile client', function () {
    $privateRoom = Room::factory()->create();
    $endedRoom = Room::factory()->public()->ended()->create();

    $this->getJson("/api/mobile/rooms/{$privateRoom->public_id}")->assertNotFound();
    $this->getJson("/api/mobile/rooms/{$endedRoom->public_id}")->assertNotFound();
});

it('returns waiting room metadata to the mobile client', function () {
    $room = Room::factory()->public()->withWaitingRoom()->create();

    $this->getJson("/api/mobile/rooms/{$room->public_id}")
        ->assertOk()
        ->assertJsonPath('room.public_id', $room->public_id)
        ->assertJsonPath('room.waiting_room_enabled', true);
});

it('returns password protected room metadata to the mobile client', function () {
    $room = Room::factory()->create([
        'password_hash' => Hash::make('secret-room-password'),
    ]);

    $this->getJson("/api/mobile/rooms/{$room->public_id}")
        ->assertOk()
        ->assertJsonPath('room.public_id', $room->public_id)
        ->assertJsonPath('room.has_password', true);
});

it('joins a public room and returns livekit credentials', function () {
    $room = Room::factory()->public()->create([
        'waiting_room_enabled' => false,
        'password_hash' => null,
    ]);

    $liveKit = Mockery::mock(LiveKitTokenService::class);
    $liveKit->shouldReceive('issueToken')
        ->once()
        ->withArgs(function (Room $issuedRoom, RoomMember $member) use ($room): bool {
            return $issuedRoom->is($room)
                && $member->display_name === 'Mobile Guest'
                && str_starts_with((string) $member->guest_id, 'guest_mobile_');
        })
        ->andReturn(new LiveKitToken(
            'wss://livekit.example.test',
            'test-token',
            'room-'.$room->public_id,
            'room-member:test',
            3600,
        ));
    app()->instance(LiveKitTokenService::class, $liveKit);

    $this->postJson("/api/mobile/rooms/{$room->public_id}/join", [
        'display_name' => 'Mobile Guest',
    ])
        ->assertCreated()
        ->assertJsonPath('room.public_id', $room->public_id)
        ->assertJsonPath('member.display_name', 'Mobile Guest')
        ->assertJsonPath('livekit.url', 'wss://livekit.example.test')
        ->assertJsonPath('livekit.token', 'test-token')
        ->assertJsonPath('livekit.room_name', 'room-'.$room->public_id)
        ->assertJsonPath('livekit.expires_in', 3600);

    $this->assertDatabaseHas('room_members', [
        'room_id' => $room->id,
        'display_name' => 'Mobile Guest',
        'left_at' => null,
    ]);
});

it('requires the correct password to join a protected room', function () {
    $room = Room::factory()->create([
        'password_hash' => Hash::make('secret-room-password'),
        'waiting_room_enabled' => false,
    ]);

    $this->postJson("/api/mobile/rooms/{$room->public_id}/join", [
        'display_name' => 'Mobile Guest',
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

it('puts a mobile guest into the waiting room', function () {
    $room = Room::factory()->public()->withWaitingRoom()->create([
        'password_hash' => null,
    ]);

    $response = $this->postJson("/api/mobile/rooms/{$room->public_id}/join", [
        'display_name' => 'Waiting Guest',
    ])
        ->assertStatus(202)
        ->assertJsonPath('room.public_id', $room->public_id)
        ->assertJsonPath('waiting_room.display_name', 'Waiting Guest')
        ->assertJsonPath('waiting_room.status', WaitingRoomStatus::Pending->value);

    $waitingToken = $response->json('waiting_token');

    expect($waitingToken)
        ->toBeString()
        ->toStartWith('guest_mobile_');

    $this->assertDatabaseHas('waiting_room_members', [
        'room_id' => $room->id,
        'guest_id' => $waitingToken,
        'display_name' => 'Waiting Guest',
        'status' => WaitingRoomStatus::Pending->value,
    ]);
});

it('returns livekit credentials after a waiting guest is approved', function () {
    $room = Room::factory()->public()->withWaitingRoom()->create([
        'password_hash' => null,
    ]);
    $guestId = 'guest_mobile_test-token';

    $entry = WaitingRoomMember::query()->create([
        'room_id' => $room->id,
        'guest_id' => $guestId,
        'display_name' => 'Approved Guest',
        'status' => WaitingRoomStatus::Approved,
        'decided_at' => now(),
    ]);

    $member = RoomMember::query()->create([
        'room_id' => $room->id,
        'guest_id' => $guestId,
        'display_name' => 'Approved Guest',
        'role' => \App\Enums\RoomRole::Participant,
        'livekit_identity' => 'room-member:test-approved',
        'joined_at' => now(),
    ]);

    $liveKit = Mockery::mock(LiveKitTokenService::class);
    $liveKit->shouldReceive('issueToken')
        ->once()
        ->withArgs(fn (Room $issuedRoom, RoomMember $issuedMember): bool => $issuedRoom->is($room) && $issuedMember->is($member))
        ->andReturn(new LiveKitToken(
            'wss://livekit.example.test',
            'approved-token',
            'room-'.$room->public_id,
            $member->livekit_identity,
            3600,
        ));
    app()->instance(LiveKitTokenService::class, $liveKit);

    $this->getJson("/api/mobile/rooms/{$room->public_id}/waiting/{$guestId}")
        ->assertOk()
        ->assertJsonPath('waiting_room.id', $entry->id)
        ->assertJsonPath('waiting_room.status', WaitingRoomStatus::Approved->value)
        ->assertJsonPath('member.id', $member->id)
        ->assertJsonPath('livekit.token', 'approved-token');
});

it('validates the mobile display name', function () {
    $room = Room::factory()->public()->create([
        'waiting_room_enabled' => false,
        'password_hash' => null,
    ]);

    $this->postJson("/api/mobile/rooms/{$room->public_id}/join", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('display_name');
});
