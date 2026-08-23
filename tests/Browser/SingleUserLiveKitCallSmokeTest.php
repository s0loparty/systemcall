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

it('connects one user to local LiveKit and controls local call state', function () {
    [$user, $room] = prepareE3OwnedRoom('E3 Single User LiveKit Room');

    $this->actingAs($user);

    $page = visit(route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false));

    $page->waitForText('connected')
        ->assertScript('document.querySelector("[data-test=connection-state]").textContent.trim()', 'connected')
        ->assertScript('document.querySelectorAll("[data-test=participant-tile][data-local=true]").length', 1)
        ->assertScript('document.querySelector("[data-test=participant-tile][data-local=true]").dataset.microphoneEnabled', 'false')
        ->assertScript('document.querySelector("[data-test=participant-tile][data-local=true]").dataset.cameraEnabled', 'false')
        ->click('@toggle-microphone')
        ->assertScript('document.querySelector("[data-test=participant-tile][data-local=true]").dataset.microphoneEnabled', 'true')
        ->click('@toggle-camera')
        ->assertScript('document.querySelector("[data-test=participant-tile][data-local=true]").dataset.cameraEnabled', 'true')
        ->assertPresent('@toggle-screen-share')
        ->click('@leave-call')
        ->assertPathEndsWith('/prepare')
        ->assertNoJavaScriptErrors();
});

it('hides screen share control when member cannot publish screen', function () {
    [$user, $room] = prepareE3OwnedRoom(
        'E3 No Screen Share Permission Room',
        canPublishScreen: false,
    );

    $this->actingAs($user);

    visit(route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false))
        ->waitForText('connected')
        ->assertScript('document.querySelector("[data-test=connection-state]").textContent.trim()', 'connected')
        ->assertNotPresent('@toggle-screen-share')
        ->assertNoJavaScriptErrors();
});

/**
 * @return array{User, Room}
 */
function prepareE3OwnedRoom(string $roomName, bool $canPublishScreen = true): array
{
    $user = User::factory()->create();
    $room = Room::factory()
        ->for($user, 'owner')
        ->public()
        ->create([
            'name' => $roomName,
            'waiting_room_enabled' => false,
        ]);

    RoomMember::factory()
        ->owner()
        ->for($room)
        ->for($user)
        ->create([
            'display_name' => $user->name,
            'can_publish_screen' => $canPublishScreen,
        ]);

    return [$user, $room];
}
