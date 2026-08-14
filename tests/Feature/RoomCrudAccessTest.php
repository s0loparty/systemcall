<?php

use App\Enums\RoomRole;
use App\Enums\RoomVisibility;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\Cookie;

test('authenticated owners can manage rooms using public ids', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $response = $this->actingAs($owner)->postJson(route('rooms.store'), [
        'name' => 'Planning call',
        'description' => 'Weekly planning',
        'visibility' => RoomVisibility::Private->value,
        'password' => 'secret-passphrase',
        'waiting_room_enabled' => true,
        'max_participants' => 12,
    ]);

    $response->assertCreated()
        ->assertJsonPath('room.name', 'Planning call')
        ->assertJsonPath('room.visibility', RoomVisibility::Private->value)
        ->assertJsonPath('room.has_password', true);

    $room = Room::firstOrFail();

    expect($room->public_id)->not->toBe((string) $room->id)
        ->and($room->password_hash)->not->toBe('secret-passphrase')
        ->and(Hash::check('secret-passphrase', $room->password_hash))->toBeTrue();

    $ownerMember = RoomMember::whereBelongsTo($room)->whereBelongsTo($owner)->firstOrFail();

    expect($ownerMember->role)->toBe(RoomRole::Owner);

    $this->actingAs($owner)
        ->getJson(route('rooms.index'))
        ->assertOk()
        ->assertJsonPath('rooms.0.public_id', $room->public_id);

    $this->actingAs($stranger)
        ->patchJson(route('rooms.update', $room), ['name' => 'Stolen'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->patchJson(route('rooms.update', $room), [
            'name' => 'Updated planning call',
            'password' => null,
        ])
        ->assertOk()
        ->assertJsonPath('room.name', 'Updated planning call')
        ->assertJsonPath('room.has_password', false);

    expect($room->fresh()->password_hash)->toBeNull();

    $this->actingAs($owner)
        ->deleteJson(route('rooms.destroy', $room))
        ->assertNoContent();

    $this->assertModelMissing($room);
});

test('authenticated users can manage rooms through Inertia pages', function () {
    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'Existing call',
        ]);

    RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->owner()
        ->create();

    $this->actingAs($owner)
        ->get(route('rooms.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Index')
            ->where('rooms.0.public_id', $room->public_id)
            ->where('rooms.0.name', 'Existing call'));

    $response = $this->actingAs($owner)
        ->post(route('rooms.store'), [
            'name' => 'Browser-created room',
            'description' => 'Created from the Vue UI',
            'visibility' => RoomVisibility::Public->value,
            'waiting_room_enabled' => '1',
            'max_participants' => '8',
        ]);

    $createdRoom = Room::query()
        ->where('name', 'Browser-created room')
        ->firstOrFail();

    $response->assertRedirect(route('rooms.call.show', $createdRoom));
    expect($createdRoom->waiting_room_enabled)->toBeTrue()
        ->and($createdRoom->max_participants)->toBe(8);
});

test('public and private room access rules are enforced', function () {
    $owner = User::factory()->create();
    $publicRoom = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    $privateRoom = Room::factory()
        ->for($owner, 'owner')
        ->create();

    $passwordRoom = Room::factory()
        ->for($owner, 'owner')
        ->create([
            'password_hash' => Hash::make('correct-password'),
        ]);

    $this->getJson(route('rooms.show', $publicRoom))
        ->assertOk()
        ->assertJsonPath('room.public_id', $publicRoom->public_id);

    $this->getJson(route('rooms.show', $privateRoom))
        ->assertForbidden();

    $this->postJson(route('rooms.memberships.store', $privateRoom), [
        'display_name' => 'Guest',
    ])->assertForbidden();

    $this->postJson(route('rooms.memberships.store', $passwordRoom), [
        'display_name' => 'Guest',
        'password' => 'wrong-password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    $this->postJson(route('rooms.memberships.store', $passwordRoom), [
        'display_name' => 'Guest',
        'password' => 'correct-password',
    ])->assertCreated()
        ->assertJsonPath('member.display_name', 'Guest')
        ->assertJsonPath('member.guest', true);
});

test('guest membership uses encrypted server issued identity and ignores browser guest id input', function () {
    $room = Room::factory()
        ->public()
        ->create();

    $response = $this->postJson(route('rooms.memberships.store', $room), [
        'display_name' => 'Browser Guest',
        'guest_id' => 'attacker-controlled',
    ]);

    $response->assertCreated();

    $member = RoomMember::whereBelongsTo($room)->firstOrFail();

    expect($member->guest_id)->toStartWith('guest_')
        ->and($member->guest_id)->not->toBe('attacker-controlled');

    $cookie = collect($response->headers->getCookies())
        ->first(fn (Cookie $cookie): bool => $cookie->getName() === 'video_call_guest_id');

    expect($cookie)->not->toBeNull();

    expect($cookie->isHttpOnly())->toBeTrue();
});
