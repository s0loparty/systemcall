<?php

use App\Models\Room;
use App\Models\RoomMember;
use App\Services\LiveKit\LiveKitToken;
use App\Services\LiveKit\LiveKitTokenService;

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

it('rejects waiting rooms in the mobile mvp', function () {
    $room = Room::factory()->public()->withWaitingRoom()->create();

    $this->getJson("/api/mobile/rooms/{$room->public_id}")
        ->assertUnprocessable();
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

it('validates the mobile display name', function () {
    $room = Room::factory()->public()->create([
        'waiting_room_enabled' => false,
        'password_hash' => null,
    ]);

    $this->postJson("/api/mobile/rooms/{$room->public_id}/join", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('display_name');
});
