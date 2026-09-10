<?php

use App\Models\Room;
use App\Models\User;
use App\Models\WaitingRoomMember;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('services.livekit.url', 'https://livekit.test');
    config()->set('services.livekit.api_key', 'test-key');
    config()->set('services.livekit.api_secret', 'test-secret-test-secret-test-secret');
    config()->set('services.livekit.token_ttl', 600);
});

test('room owner sees pending waiting room entries before membership is created', function () {
    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create(['waiting_room_enabled' => true]);

    $entry = WaitingRoomMember::factory()
        ->for($room)
        ->guest()
        ->create(['display_name' => 'Mobile Guest']);

    expect($room->members()->whereBelongsTo($owner)->exists())->toBeFalse();

    $this->actingAs($owner)
        ->get(route('rooms.call.show', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Call')
            ->where('can_moderate_waiting_room', true)
            ->where('can_moderate_members', true)
            ->where('waiting_room_entries.0.id', $entry->id)
        );
});

test('room owner can fetch pending waiting room entries before membership is created', function () {
    $owner = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create(['waiting_room_enabled' => true]);

    $entry = WaitingRoomMember::factory()
        ->for($room)
        ->guest()
        ->create(['display_name' => 'Mobile Guest']);

    expect($room->members()->whereBelongsTo($owner)->exists())->toBeFalse();

    $this->actingAs($owner)
        ->getJson(route('rooms.waiting_room.index', $room))
        ->assertOk()
        ->assertJsonPath('waiting_room.0.id', $entry->id);
});
