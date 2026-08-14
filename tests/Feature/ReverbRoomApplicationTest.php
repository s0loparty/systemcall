<?php

use App\Broadcasting\RoomApplicationChannel;
use App\Enums\RoomRole;
use App\Events\Rooms\RoomMemberJoined;
use App\Models\Room;
use App\Models\RoomBan;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-reverb-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-reverb-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-reverb-app');
});

test('authenticated active room members can authorize the private application channel', function () {
    $user = User::factory()->create();
    $room = Room::factory()
        ->for($user, 'owner')
        ->public()
        ->create();

    RoomMember::factory()
        ->for($room)
        ->for($user)
        ->create([
            'role' => RoomRole::Owner,
        ]);

    expect(app(RoomApplicationChannel::class)->join($user, $room->public_id))->toBeTrue();

    $this->actingAs($user, 'web')
        ->post('/broadcasting/room-auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-room.'.$room->public_id,
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('non members and blocked members cannot authorize the private application channel', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    $this->actingAs($stranger, 'web')
        ->post('/broadcasting/room-auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-room.'.$room->public_id,
        ])
        ->assertForbidden();

    RoomMember::factory()
        ->for($room)
        ->for($stranger)
        ->create();

    RoomBan::factory()
        ->for($room)
        ->for($stranger)
        ->create();

    $this->actingAs($stranger, 'web')
        ->post('/broadcasting/room-auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-room.'.$room->public_id,
        ])
        ->assertForbidden();

    $room->bans()->delete();
    $room->update(['waiting_room_enabled' => true]);

    $this->actingAs($stranger, 'web')
        ->post('/broadcasting/room-auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-room.'.$room->public_id,
        ])
        ->assertForbidden();
});

test('guest members authorize through the server issued guest cookie', function () {
    $room = Room::factory()
        ->public()
        ->create();

    $guestMember = RoomMember::factory()
        ->for($room)
        ->create([
            'user_id' => null,
            'guest_id' => 'guest_'.str_repeat('a', 40),
            'display_name' => 'Browser Guest',
        ]);

    $this->withCredentials()
        ->withCookie('video_call_guest_id', $guestMember->guest_id)
        ->post('/broadcasting/room-auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-room.'.$room->public_id,
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('first room membership broadcasts an application join event', function () {
    $room = Room::factory()
        ->public()
        ->create();

    Event::fake([RoomMemberJoined::class]);

    $this->postJson(route('rooms.memberships.store', $room), [
        'display_name' => 'Browser Guest',
    ])->assertCreated();

    Event::assertDispatched(RoomMemberJoined::class, function (RoomMemberJoined $event) use ($room): bool {
        $payload = $event->broadcastWith();

        expect($event->broadcastAs())->toBe('room.member.joined')
            ->and($event->broadcastOn()[0]->name)->toBe('private-room.'.$room->public_id)
            ->and($payload['type'])->toBe('member_joined')
            ->and($payload['room_public_id'])->toBe($room->public_id)
            ->and($payload['member']['display_name'])->toBe('Browser Guest')
            ->and($payload['member']['guest'])->toBeTrue();

        return true;
    });
});
