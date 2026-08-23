<?php

use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('queue.default', 'sync');
    config()->set('services.livekit.url', 'ws://livekit:7880');
    config()->set('services.livekit.api_key', 'devkey');
    config()->set('services.livekit.api_secret', 'local-livekit-secret-local-livekit-secret');
    config()->set('services.livekit.token_ttl', 600);
});

it('propagates raised hand state through Reverb between call participants', function () {
    [$owner, $participant, $room, $ownerMember] = prepareE7Room();
    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait(3)
        ->assertScript(e7ConnectionState(), 'connected')
        ->assertScript(e7EchoConnectionState(), 'connected')
        ->assertScript(e7RaisedHandsCount(), '0')
        ->assertScript(e7ControlHandRaised(), 'false');

    $this->actingAs($participant);
    $participantPage = visit($callUrl)
        ->wait(3)
        ->assertScript(e7ConnectionState(), 'connected')
        ->assertScript(e7EchoConnectionState(), 'connected')
        ->assertScript(e7RaisedHandsCount(), '0')
        ->assertScript(e7TileCount(), 2);

    $ownerPage->wait(2)
        ->assertScript(e7TileCount(), 2);

    $this->actingAs($owner);
    $ownerPage->click('@toggle-hand')
        ->wait(2)
        ->assertScript(e7ControlHandRaised(), 'true')
        ->assertScript(e7RaisedHandsCount(), '1')
        ->assertScript(e7TileDataset($ownerMember->livekit_identity, 'handRaised'), 'true')
        ->assertScript(e7TileHandBadgeCount($ownerMember->livekit_identity), 1);

    expect($ownerMember->fresh()->hand_raised_at)->not->toBeNull();

    $participantPage->wait(4)
        ->assertScript(e7RaisedHandsCount(), '1')
        ->assertScript(e7TileDataset($ownerMember->livekit_identity, 'handRaised'), 'true')
        ->assertScript(e7TileHandBadgeCount($ownerMember->livekit_identity), 1);

    $this->actingAs($owner);
    $ownerPage->click('@toggle-hand')
        ->wait(2)
        ->assertScript(e7ControlHandRaised(), 'false')
        ->assertScript(e7RaisedHandsCount(), '0')
        ->assertScript(e7TileDataset($ownerMember->livekit_identity, 'handRaised'), 'false')
        ->assertScript(e7TileHandBadgeCount($ownerMember->livekit_identity), 0);

    expect($ownerMember->fresh()->hand_raised_at)->toBeNull();

    $participantPage->wait(4)
        ->assertScript(e7RaisedHandsCount(), '0')
        ->assertScript(e7TileDataset($ownerMember->livekit_identity, 'handRaised'), 'false')
        ->assertScript(e7TileHandBadgeCount($ownerMember->livekit_identity), 0);

    $this->actingAs($owner);
    $ownerPage->click('@toggle-hand')
        ->wait(2)
        ->assertScript(e7RaisedHandsCount(), '1')
        ->assertScript(e7ActiveHandTileCount(), 1);

    $participantPage->wait(4)
        ->assertScript(e7RaisedHandsCount(), '1')
        ->assertScript(e7ActiveHandTileCount(), 1);

    $this->actingAs($participant);
    $participantPage->refresh()
        ->wait(5)
        ->assertScript(e7ConnectionState(), 'connected')
        ->assertScript(e7EchoConnectionState(), 'connected')
        ->assertScript(e7RaisedHandsCount(), '1')
        ->assertScript(e7TileDataset($ownerMember->livekit_identity, 'handRaised'), 'true')
        ->assertNoJavaScriptErrors();

    $this->actingAs($owner);
    $ownerPage->click('@toggle-hand')
        ->wait(2)
        ->assertScript(e7RaisedHandsCount(), '0')
        ->assertNoJavaScriptErrors();

    $participantPage->wait(4)
        ->assertScript(e7RaisedHandsCount(), '0')
        ->assertScript(e7ActiveHandTileCount(), 0)
        ->assertNoJavaScriptErrors();
});

/**
 * @return array{User, User, Room, RoomMember}
 */
function prepareE7Room(): array
{
    $owner = User::factory()->create([
        'name' => 'E7 Owner User',
    ]);
    $participant = User::factory()->create([
        'name' => 'E7 Participant User',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'E7 Raised Hand Room',
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

function e7ConnectionState(): string
{
    return 'document.querySelector("[data-test=connection-state]").textContent.trim()';
}

function e7EchoConnectionState(): string
{
    return 'document.querySelector("[data-test=echo-connection-state]").textContent.replace("Reverb:", "").trim()';
}

function e7RaisedHandsCount(): string
{
    return 'document.querySelector("[data-test=raised-hands-count]").dataset.count';
}

function e7ControlHandRaised(): string
{
    return 'document.querySelector("[data-test=toggle-hand]").dataset.handRaised';
}

function e7TileCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-tile]").length';
}

function e7TileDataset(string $identity, string $key): string
{
    return 'document.querySelector('.json_encode(e7TileSelector($identity)).').dataset.'.$key;
}

function e7TileHandBadgeCount(string $identity): string
{
    return 'document.querySelectorAll('.json_encode(e7TileSelector($identity).' [data-test=participant-hand-raised-badge]').').length';
}

function e7ActiveHandTileCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-tile][data-hand-raised=true]").length';
}

function e7TileSelector(string $identity): string
{
    return '[data-test=participant-tile][data-identity="'.$identity.'"]';
}
