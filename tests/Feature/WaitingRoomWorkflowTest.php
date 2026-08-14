<?php

use App\Enums\RoomRole;
use App\Enums\WaitingRoomStatus;
use App\Events\Rooms\RoomMemberJoined;
use App\Events\Rooms\WaitingRoomEntryDecided;
use App\Events\Rooms\WaitingRoomEntryRequested;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use App\Models\WaitingRoomMember;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-reverb-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-reverb-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-reverb-app');
    config()->set('services.livekit.url', 'https://livekit.test');
    config()->set('services.livekit.api_key', 'test-key');
    config()->set('services.livekit.api_secret', 'test-secret-test-secret-test-secret');
    config()->set('services.livekit.token_ttl', 600);
});

test('guests request waiting-room approval before membership or token issuance', function () {
    Event::fake([WaitingRoomEntryRequested::class]);

    $room = Room::factory()
        ->public()
        ->create(['waiting_room_enabled' => true]);

    $response = $this->postJson(route('rooms.memberships.store', $room), [
        'display_name' => 'Waiting Guest',
    ]);

    $response->assertAccepted()
        ->assertJsonPath('waiting_room.display_name', 'Waiting Guest')
        ->assertJsonPath('waiting_room.status', WaitingRoomStatus::Pending->value);

    $entry = WaitingRoomMember::whereBelongsTo($room)->firstOrFail();
    $cookie = collect($response->headers->getCookies())
        ->first(fn ($cookie): bool => $cookie->getName() === 'video_call_guest_id');

    expect($entry->status)->toBe(WaitingRoomStatus::Pending)
        ->and($entry->guest_id)->not->toBeNull()
        ->and($room->members)->toHaveCount(0)
        ->and($cookie)->not->toBeNull();

    Event::assertDispatched(WaitingRoomEntryRequested::class, function (WaitingRoomEntryRequested $event) use ($entry, $room): bool {
        $payload = $event->broadcastWith();

        expect($event->broadcastAs())->toBe('waiting-room.entry.requested')
            ->and($event->broadcastOn()[0]->name)->toBe('private-room.'.$room->public_id)
            ->and($payload['waiting_room']['id'])->toBe($entry->id);

        return true;
    });

    $this->withCredentials()
        ->withCookie('video_call_guest_id', $entry->guest_id)
        ->post('/broadcasting/room-auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-room.'.$room->public_id,
        ])
        ->assertOk();

    $this->withCredentials()
        ->withCookie('video_call_guest_id', $entry->guest_id)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();
});

test('moderators can list and approve waiting-room entries before token issuance', function () {
    Event::fake([
        RoomMemberJoined::class,
        WaitingRoomEntryDecided::class,
    ]);

    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create(['waiting_room_enabled' => true]);

    RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->create(['role' => RoomRole::Owner]);

    $entry = WaitingRoomMember::factory()
        ->for($room)
        ->guest()
        ->create([
            'display_name' => 'Approved Guest',
        ]);

    $this->actingAs($owner)
        ->getJson(route('rooms.waiting_room.index', $room))
        ->assertOk()
        ->assertJsonPath('waiting_room.0.id', $entry->id);

    $this->actingAs($owner)
        ->postJson(route('rooms.waiting_room.approve', [$room, $entry]))
        ->assertOk()
        ->assertJsonPath('waiting_room.status', WaitingRoomStatus::Approved->value);

    $entry->refresh();
    $member = RoomMember::whereBelongsTo($room)
        ->where('guest_id', $entry->guest_id)
        ->firstOrFail();

    expect($entry->status)->toBe(WaitingRoomStatus::Approved)
        ->and($entry->decided_by_user_id)->toBe($owner->id)
        ->and($member->display_name)->toBe('Approved Guest');

    Event::assertDispatched(RoomMemberJoined::class);
    Event::assertDispatched(WaitingRoomEntryDecided::class, function (WaitingRoomEntryDecided $event) use ($entry, $room, $member): bool {
        $payload = $event->broadcastWith();

        expect($event->broadcastAs())->toBe('waiting-room.entry.decided')
            ->and($event->broadcastOn()[0]->name)->toBe('private-room.'.$room->public_id)
            ->and($payload['waiting_room']['id'])->toBe($entry->id)
            ->and($payload['waiting_room']['status'])->toBe(WaitingRoomStatus::Approved->value)
            ->and($payload['member']['id'])->toBe($member->id);

        return true;
    });

    auth()->guard()->logout();

    $this->withCredentials()
        ->withCookie('video_call_guest_id', $entry->guest_id)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertOk()
        ->assertJsonPath('livekit.identity', $member->livekit_identity);
});

test('approved participants can reuse their membership without creating a new pending entry', function () {
    $room = Room::factory()
        ->public()
        ->create(['waiting_room_enabled' => true]);

    $entry = WaitingRoomMember::factory()
        ->for($room)
        ->guest()
        ->approved()
        ->create();
    $member = RoomMember::factory()
        ->for($room)
        ->create([
            'user_id' => null,
            'guest_id' => $entry->guest_id,
            'display_name' => $entry->display_name,
        ]);

    $this->withCredentials()
        ->withCookie('video_call_guest_id', $entry->guest_id)
        ->postJson(route('rooms.memberships.store', $room), [
            'display_name' => 'Ignored Pending Name',
        ])
        ->assertOk()
        ->assertJsonPath('member.id', $member->id);

    expect(
        WaitingRoomMember::whereBelongsTo($room)
            ->where('status', WaitingRoomStatus::Pending)
            ->exists()
    )->toBeFalse();
});

test('moderators can reject waiting-room entries without creating room members', function () {
    Event::fake([WaitingRoomEntryDecided::class]);

    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create(['waiting_room_enabled' => true]);

    RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->create(['role' => RoomRole::Owner]);

    $entry = WaitingRoomMember::factory()
        ->for($room)
        ->guest()
        ->create();

    $this->actingAs($owner)
        ->postJson(route('rooms.waiting_room.reject', [$room, $entry]))
        ->assertOk()
        ->assertJsonPath('waiting_room.status', WaitingRoomStatus::Rejected->value);

    expect($entry->fresh()->status)->toBe(WaitingRoomStatus::Rejected)
        ->and(RoomMember::whereBelongsTo($room)->where('guest_id', $entry->guest_id)->exists())->toBeFalse();

    Event::assertDispatched(WaitingRoomEntryDecided::class);

    auth()->guard()->logout();

    $this->withCredentials()
        ->withCookie('video_call_guest_id', $entry->guest_id)
        ->post('/broadcasting/room-auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-room.'.$room->public_id,
        ])
        ->assertForbidden();
});

test('stale pending waiting-room entries are pruned without deleting decided entries', function () {
    config()->set('video-calls.waiting_room.stale_minutes', 30);

    $oldPending = WaitingRoomMember::factory()
        ->create([
            'created_at' => now()->subMinutes(31),
            'updated_at' => now()->subMinutes(31),
        ]);
    $recentPending = WaitingRoomMember::factory()
        ->create([
            'created_at' => now()->subMinutes(29),
            'updated_at' => now()->subMinutes(29),
        ]);
    $approved = WaitingRoomMember::factory()
        ->approved()
        ->create([
            'created_at' => now()->subMinutes(60),
            'updated_at' => now()->subMinutes(60),
        ]);

    $this->artisan('rooms:prune-stale-waiting-room-entries')
        ->assertSuccessful();

    $this->assertModelMissing($oldPending);
    $this->assertModelExists($recentPending);
    $this->assertModelExists($approved);
});
