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

it('enters and leaves screen share layout for both LiveKit participants', function () {
    [$owner, $participant, $room, $ownerMember] = prepareE6Room();
    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait(3)
        ->assertScript(e6ConnectionState(), 'connected')
        ->assertPresent('@toggle-screen-share')
        ->assertScript(e6NormalGridCount(), 1);

    $this->actingAs($participant);
    $participantPage = visit($callUrl)
        ->wait(3)
        ->assertScript(e6ConnectionState(), 'connected')
        ->assertScript(e6TileCount(), 2)
        ->assertScript(e6NormalGridCount(), 1);

    $ownerPage->wait(2)
        ->assertScript(e6TileCount(), 2);

    $ownerPage->click('@toggle-screen-share')
        ->wait(3)
        ->assertScript(e6TileDataset($ownerMember->livekit_identity, 'screenShareEnabled'), 'true')
        ->assertScript(e6ScreenShareLayoutCount(), 1)
        ->assertScript(e6SideRailCount(), 1)
        ->assertScript(e6ScreenShareVideoCount($ownerMember->livekit_identity), 1)
        ->assertScript(e6CameraVideoCount($ownerMember->livekit_identity), 0);

    $participantPage->wait(4)
        ->assertScript(e6TileDataset($ownerMember->livekit_identity, 'screenShareEnabled'), 'true')
        ->assertScript(e6ScreenShareLayoutCount(), 1)
        ->assertScript(e6SideRailCount(), 1)
        ->assertScript(e6ScreenShareVideoCount($ownerMember->livekit_identity), 1)
        ->assertScript(e6CameraVideoCount($ownerMember->livekit_identity), 0)
        ->assertSee('показ экрана');

    $ownerPage->click('@toggle-screen-share')
        ->wait(3)
        ->assertScript(e6ScreenShareLayoutCount(), 0)
        ->assertScript(e6NormalGridCount(), 1)
        ->assertScript(e6ActiveScreenShareTileCount(), 0)
        ->assertScript(e6ScreenShareVideoCount($ownerMember->livekit_identity), 0);

    $participantPage->wait(4)
        ->assertScript(e6ScreenShareLayoutCount(), 0)
        ->assertScript(e6NormalGridCount(), 1)
        ->assertScript(e6ActiveScreenShareTileCount(), 0)
        ->assertScript(e6ScreenShareVideoCount($ownerMember->livekit_identity), 0)
        ->assertNoJavaScriptErrors();

    $ownerPage->assertNoJavaScriptErrors();
});

/**
 * @return array{User, User, Room, RoomMember}
 */
function prepareE6Room(): array
{
    $owner = User::factory()->create([
        'name' => 'E6 Owner User',
    ]);
    $participant = User::factory()->create([
        'name' => 'E6 Participant User',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'E6 Screen Share Room',
            'waiting_room_enabled' => false,
        ]);

    $ownerMember = RoomMember::factory()
        ->owner()
        ->for($room)
        ->for($owner)
        ->create([
            'display_name' => $owner->name,
            'can_publish_screen' => true,
        ]);

    RoomMember::factory()
        ->for($room)
        ->for($participant)
        ->create([
            'display_name' => $participant->name,
        ]);

    return [$owner, $participant, $room, $ownerMember];
}

function e6TileCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-tile]").length';
}

function e6ConnectionState(): string
{
    return 'document.querySelector("[data-test=connection-state]").textContent.trim()';
}

function e6TileDataset(string $identity, string $key): string
{
    return 'document.querySelector('.json_encode(e6TileSelector($identity)).').dataset.'.$key;
}

function e6ScreenShareLayoutCount(): string
{
    return 'document.querySelectorAll("[data-test=screen-share-layout]").length';
}

function e6NormalGridCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-grid]").length';
}

function e6SideRailCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-side-rail]").length';
}

function e6ActiveScreenShareTileCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-tile][data-screen-share-enabled=true]").length';
}

function e6ScreenShareVideoCount(string $identity): string
{
    return 'document.querySelectorAll('.json_encode(e6TileSelector($identity).' video[data-source="screen-share"]').').length';
}

function e6CameraVideoCount(string $identity): string
{
    return 'document.querySelectorAll('.json_encode(e6TileSelector($identity).' video[data-source="camera"]').').length';
}

function e6TileSelector(string $identity): string
{
    return '[data-test=participant-tile][data-identity="'.$identity.'"]';
}
