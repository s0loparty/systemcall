<?php

use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;

beforeEach(function () {
    config()->set('services.livekit.url', 'ws://livekit:7880');
    config()->set('services.livekit.api_key', 'devkey');
    config()->set('services.livekit.api_secret', 'local-livekit-secret-local-livekit-secret');
    config()->set('services.livekit.token_ttl', 600);
});

it('shows multi-user LiveKit presence and removes disconnected participants', function () {
    [$owner, $participant, $room] = prepareE4Room();

    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait(3)
        ->assertScript('document.querySelector("[data-test=connection-state]").textContent.trim()', 'connected')
        ->assertSee($owner->name)
        ->assertScript('document.querySelectorAll("[data-test=participant-tile]").length', 1);

    $this->actingAs($participant);
    $participantPage = visit($callUrl)
        ->wait(3)
        ->assertScript('document.querySelector("[data-test=connection-state]").textContent.trim()', 'connected')
        ->assertSee($participant->name);

    $ownerPage->wait(2)
        ->assertSee($participant->name)
        ->assertScript('document.querySelectorAll("[data-test=participant-tile]").length', 2);

    $participantPage->wait(1)
        ->assertSee($owner->name)
        ->assertScript('document.querySelectorAll("[data-test=participant-tile]").length', 2);

    $ownerPage->click('@leave-call')
        ->assertPathEndsWith('/prepare');

    $participantPage->wait(2)
        ->assertSee($participant->name)
        ->assertScript('document.querySelectorAll("[data-test=participant-tile]").length', 1)
        ->refresh()
        ->wait(3)
        ->assertScript('document.querySelector("[data-test=connection-state]").textContent.trim()', 'connected')
        ->assertScript('document.querySelectorAll("[data-test=participant-tile]").length', 1)
        ->assertScript('new Set([...document.querySelectorAll("[data-test=participant-tile]")].map((tile) => tile.dataset.identity)).size', 1)
        ->assertNoJavaScriptErrors();

    $ownerPage->assertNoJavaScriptErrors();
});

/**
 * @return array{User, User, Room}
 */
function prepareE4Room(): array
{
    $owner = User::factory()->create([
        'name' => 'E4 Owner User',
    ]);
    $participant = User::factory()->create([
        'name' => 'E4 Participant User',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'E4 Multi User LiveKit Room',
            'waiting_room_enabled' => false,
        ]);

    RoomMember::factory()
        ->owner()
        ->for($room)
        ->for($owner)
        ->create([
            'display_name' => $owner->name,
        ]);

    RoomMember::factory()
        ->for($room)
        ->for($participant)
        ->create([
            'display_name' => $participant->name,
        ]);

    return [$owner, $participant, $room];
}
