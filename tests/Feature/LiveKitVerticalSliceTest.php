<?php

use App\Enums\RoomRole;
use App\Models\Room;
use App\Models\RoomBan;
use App\Models\RoomMember;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('services.livekit.url', 'https://livekit.test');
    config()->set('services.livekit.api_key', 'test-key');
    config()->set('services.livekit.api_secret', 'test-secret-test-secret-test-secret');
    config()->set('services.livekit.token_ttl', 600);
});

test('room call flow pages render the LiveKit vertical slice props', function (string $routeName, string $component) {
    $user = User::factory()->create();
    $room = Room::factory()
        ->public()
        ->create([
            'name' => 'Daily standup',
        ]);

    $this->actingAs($user)
        ->get(route($routeName, $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('room.public_id', $room->public_id)
            ->where('room.name', 'Daily standup')
            ->where('room.livekit_room_name', 'room-'.$room->public_id)
            ->where('can_join_without_password', true));
})->with([
    'prepare page' => ['rooms.call.prepare', 'rooms/Prepare'],
    'waiting page' => ['rooms.call.waiting', 'rooms/Waiting'],
    'call page' => ['rooms.call.show', 'rooms/Call'],
]);

test('guests can open room preparation pages from shared links', function () {
    $room = Room::factory()
        ->public()
        ->create([
            'name' => 'Shared guest room',
        ]);

    $this->get(route('rooms.call.prepare', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Prepare')
            ->where('room.public_id', $room->public_id)
            ->where('room.name', 'Shared guest room'));
});

test('guests without server issued identity are redirected from call pages to preparation', function (string $routeName) {
    $room = Room::factory()
        ->public()
        ->create();

    $this->get(route($routeName, $room))
        ->assertRedirect(route('rooms.call.prepare', $room));
})->with([
    'waiting page' => 'rooms.call.waiting',
    'call page' => 'rooms.call.show',
]);

test('guest shared links to password protected rooms require password on preparation page', function () {
    $room = Room::factory()
        ->public()
        ->create([
            'name' => 'Password guest room',
            'password_hash' => Hash::make('correct-password'),
        ]);

    $this->get(route('rooms.call.show', $room))
        ->assertRedirect(route('rooms.call.prepare', $room));

    $this->get(route('rooms.call.prepare', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Prepare')
            ->where('room.public_id', $room->public_id)
            ->where('room.has_password', true)
            ->where('can_join_without_password', false));
});

test('room owners can open password protected rooms without re-entering password', function () {
    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'password_hash' => Hash::make('correct-password'),
        ]);

    $this->actingAs($owner)
        ->get(route('rooms.call.prepare', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Prepare')
            ->where('room.has_password', true)
            ->where('can_join_without_password', true));
});

test('guest room joins require a display name before membership creation', function () {
    $room = Room::factory()
        ->public()
        ->create();

    $this->postJson(route('rooms.memberships.store', $room), [
        'display_name' => '',
    ])->assertInvalid(['display_name']);

    expect(RoomMember::whereBelongsTo($room)->exists())->toBeFalse();
});

test('room owners keep moderation rights after leaving and joining again', function () {
    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    RoomMember::factory()
        ->owner()
        ->for($room)
        ->for($owner)
        ->create([
            'left_at' => now(),
        ]);

    $joinResponse = $this->actingAs($owner)
        ->postJson(route('rooms.memberships.store', $room));

    $joinResponse
        ->assertCreated()
        ->assertJsonPath('member.role', RoomRole::Owner->value);

    $member = RoomMember::query()
        ->whereBelongsTo($room)
        ->whereBelongsTo($owner)
        ->whereNull('left_at')
        ->firstOrFail();

    expect($member->role)->toBe(RoomRole::Owner);

    $this->actingAs($owner)
        ->get(route('rooms.call.show', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('current_room_member_id', $member->id)
            ->where('room_members.0.left_at', null)
            ->where('can_moderate_members', true));
});

test('room owner active memberships are normalized after stale participant role regression', function () {
    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();
    $member = RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->create([
            'role' => RoomRole::Participant,
        ]);

    $this->actingAs($owner)
        ->postJson(route('rooms.memberships.store', $room))
        ->assertCreated()
        ->assertJsonPath('member.id', $member->id)
        ->assertJsonPath('member.role', RoomRole::Owner->value);

    expect($member->fresh()->role)->toBe(RoomRole::Owner);
});

test('call page active member payload keeps explicit left_at null values', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    RoomMember::factory()
        ->owner()
        ->for($room)
        ->for($owner)
        ->create();

    $participantMember = RoomMember::factory()
        ->for($room)
        ->for($participant)
        ->create();

    $response = $this->actingAs($owner)
        ->get(route('rooms.call.show', $room))
        ->assertOk();

    $members = collect($response->inertiaProps('room_members'));
    $payload = $members->firstWhere('id', $participantMember->id);

    expect($payload)->not->toBeNull()
        ->and($payload['left_at'])->toBeNull();
});

test('guests with server issued identity can open the call page', function () {
    $room = Room::factory()
        ->public()
        ->create();

    $joinResponse = $this->postJson(route('rooms.memberships.store', $room), [
        'display_name' => 'Invited Guest',
    ]);

    $joinResponse->assertCreated();

    $member = RoomMember::whereBelongsTo($room)->firstOrFail();

    $this
        ->withCredentials()
        ->withCookie('video_call_guest_id', $member->guest_id)
        ->get(route('rooms.call.show', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Call')
            ->where('current_room_member_id', $member->id)
            ->where('room_members.0.display_name', 'Invited Guest'));
});

test('active room members can obtain scoped LiveKit tokens', function () {
    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    $member = RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->create([
            'display_name' => 'Owner',
            'role' => RoomRole::Owner,
            'can_publish_screen' => false,
        ]);

    $response = $this->actingAs($owner)
        ->postJson(route('rooms.livekit_token.store', $room));

    $response->assertOk()
        ->assertJsonPath('livekit.url', 'https://livekit.test')
        ->assertJsonPath('livekit.room_name', 'room-'.$room->public_id)
        ->assertJsonPath('livekit.identity', $member->livekit_identity)
        ->assertJsonPath('livekit.expires_in', 600);

    $payload = (array) JWT::decode(
        $response->json('livekit.token'),
        new Key('test-secret-test-secret-test-secret', 'HS256'),
    );

    $videoGrant = (array) $payload['video'];

    expect($payload['iss'])->toBe('test-key')
        ->and($payload['sub'])->toBe($member->livekit_identity)
        ->and($videoGrant['room'])->toBe('room-'.$room->public_id)
        ->and($videoGrant['roomJoin'])->toBeTrue()
        ->and($videoGrant['canPublish'])->toBeTrue()
        ->and($videoGrant['canSubscribe'])->toBeTrue()
        ->and($videoGrant['canPublishSources'])->toBe(['camera', 'microphone']);
});

test('unauthorized participants cannot obtain LiveKit tokens', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    $this->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();
});

test('guests must use the server issued identity before token issuance', function () {
    $room = Room::factory()
        ->public()
        ->create();

    $joinResponse = $this->postJson(route('rooms.memberships.store', $room), [
        'display_name' => 'Browser Guest',
    ]);

    $joinResponse->assertCreated();

    $cookie = collect($joinResponse->headers->getCookies())
        ->first(fn ($cookie): bool => $cookie->getName() === 'video_call_guest_id');

    expect($cookie)->not->toBeNull();

    $member = RoomMember::whereBelongsTo($room)->firstOrFail();
    expect($member->guest_id)->not->toBeNull();

    $tokenResponse = $this
        ->withCredentials()
        ->withCookie('video_call_guest_id', $member->guest_id)
        ->postJson(route('rooms.livekit_token.store', $room));

    $tokenResponse->assertOk()
        ->assertJsonPath('livekit.identity', $member->livekit_identity);
});

test('banned or waiting-room participants cannot obtain LiveKit tokens', function () {
    $user = User::factory()->create();
    $room = Room::factory()
        ->for($user, 'owner')
        ->public()
        ->create();

    RoomMember::factory()
        ->for($room)
        ->for($user)
        ->create();

    RoomBan::factory()
        ->for($room)
        ->for($user)
        ->create();

    $this->actingAs($user)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();

    $room->bans()->delete();
    $room->update(['waiting_room_enabled' => true]);

    $this->actingAs($user)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();
});
