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

it('propagates audio and video controls between two LiveKit participants', function () {
    [$owner, $participant, $room, $ownerMember] = prepareE5Room();
    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait(3)
        ->assertScript(e5ConnectionState(), 'connected')
        ->assertScript(e5TileCount(), 1);

    $this->actingAs($participant);
    $participantPage = visit($callUrl)
        ->wait(3)
        ->assertScript(e5ConnectionState(), 'connected')
        ->assertSee($owner->name);

    $ownerPage->wait(2)
        ->assertScript(e5TileCount(), 2);

    $participantPage->wait(1)
        ->assertScript(e5TileCount(), 2)
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'microphoneEnabled'), 'false')
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'cameraEnabled'), 'false')
        ->assertScript(e5TileVideoCount($ownerMember->livekit_identity), 0)
        ->assertScript(e5TileFallbackCount($ownerMember->livekit_identity), 1);

    $ownerPage->click('@toggle-microphone');
    $participantPage->wait(2)
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'microphoneEnabled'), 'true');

    $ownerPage->click('@toggle-microphone');
    $participantPage->wait(2)
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'microphoneEnabled'), 'false');

    $ownerPage->click('@toggle-camera');
    $participantPage->wait(3)
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'cameraEnabled'), 'true')
        ->assertScript(e5TileVideoCount($ownerMember->livekit_identity), 1)
        ->assertScript(e5TileFallbackCount($ownerMember->livekit_identity), 0);

    $ownerPage->click('@toggle-camera');
    $participantPage->wait(2)
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'cameraEnabled'), 'false')
        ->assertScript(e5TileVideoCount($ownerMember->livekit_identity), 0)
        ->assertScript(e5TileFallbackCount($ownerMember->livekit_identity), 1);

    $ownerPage->click('@toggle-microphone')
        ->click('@toggle-camera');

    $participantPage->wait(3)
        ->assertSee($owner->name)
        ->assertScript(e5TileCount(), 2)
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'microphoneEnabled'), 'true')
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'cameraEnabled'), 'true')
        ->assertScript(e5TileVideoCount($ownerMember->livekit_identity), 1);

    $participantPage->refresh()
        ->wait(5)
        ->assertScript(e5ConnectionState(), 'connected')
        ->assertScript(e5TileCount(), 2)
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'microphoneEnabled'), 'true')
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'cameraEnabled'), 'true')
        ->assertScript(e5TileVideoCount($ownerMember->livekit_identity), 1);

    $ownerPage->click('@toggle-microphone')
        ->click('@toggle-camera')
        ->click('@toggle-microphone')
        ->click('@toggle-camera');

    $participantPage->wait(4)
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'microphoneEnabled'), 'true')
        ->assertScript(e5TileDataset($ownerMember->livekit_identity, 'cameraEnabled'), 'true')
        ->assertScript(e5TileVideoCount($ownerMember->livekit_identity), 1)
        ->assertNoJavaScriptErrors();

    $ownerPage->assertNoJavaScriptErrors();
});

/**
 * @return array{User, User, Room, RoomMember}
 */
function prepareE5Room(): array
{
    $owner = User::factory()->create([
        'name' => 'E5 Owner User',
    ]);
    $participant = User::factory()->create([
        'name' => 'E5 Participant User',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'E5 Audio Video Room',
            'waiting_room_enabled' => false,
        ]);

    $ownerMember = RoomMember::factory()
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

    return [$owner, $participant, $room, $ownerMember];
}

function e5TileCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-tile]").length';
}

function e5ConnectionState(): string
{
    return 'document.querySelector("[data-test=connection-state]").textContent.trim()';
}

function e5TileDataset(string $identity, string $key): string
{
    return 'document.querySelector('.json_encode(e5TileSelector($identity)).').dataset.'.$key;
}

function e5TileVideoCount(string $identity): string
{
    return 'document.querySelectorAll('.json_encode(e5TileSelector($identity).' video').').length';
}

function e5TileFallbackCount(string $identity): string
{
    return 'document.querySelectorAll('.json_encode(e5TileSelector($identity).' [data-test=participant-fallback-avatar]').').length';
}

function e5TileSelector(string $identity): string
{
    return '[data-test=participant-tile][data-identity="'.$identity.'"]';
}
