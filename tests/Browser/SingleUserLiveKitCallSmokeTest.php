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

    $page->wait(6)
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
        ->wait(6)
        ->assertScript('document.querySelector("[data-test=connection-state]").textContent.trim()', 'connected')
        ->assertNotPresent('@toggle-screen-share')
        ->assertNoJavaScriptErrors();
});

it('auto hides desktop call controls after mouse inactivity and shows them on movement', function () {
    [$user, $room] = prepareE3OwnedRoom('E3 Desktop Controls Auto Hide Room');

    $this->actingAs($user);

    visit(route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false))
        ->wait(6)
        ->assertScript(e3CallControlsHidden(), 'false')
        ->wait(11)
        ->assertScript(e3CallControlsHidden(), 'true')
        ->assertNotPresent('@show-call-controls-tab')
        ->assertScript(e3MoveMouseOverCall(), true)
        ->wait()
        ->assertScript(e3CallControlsHidden(), 'false')
        ->assertNoJavaScriptErrors();
});

it('auto hides mobile call controls and shows a reveal tab', function () {
    [$user, $room] = prepareE3OwnedRoom('E3 Mobile Controls Auto Hide Room');

    $this->actingAs($user);

    visit(route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false))
        ->resize(390, 844)
        ->wait(6)
        ->assertScript(e3CallControlsMobile(), 'true')
        ->assertScript(e3CallControlsHidden(), 'false')
        ->wait(11)
        ->assertScript(e3CallControlsHidden(), 'true')
        ->assertPresent('@show-call-controls-tab')
        ->click('@show-call-controls-tab')
        ->wait()
        ->assertScript(e3CallControlsHidden(), 'false')
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

function e3CallControlsHidden(): string
{
    return 'document.querySelector("[data-test=call-controls-bar]").dataset.hidden';
}

function e3CallControlsMobile(): string
{
    return 'document.querySelector("[data-test=call-controls-bar]").dataset.mobile';
}

function e3MoveMouseOverCall(): string
{
    return <<<'JS'
        (() => {
            window.dispatchEvent(new MouseEvent("mousemove", {
                bubbles: true,
                clientX: window.innerWidth / 2,
                clientY: window.innerHeight / 2
            }));

            return true;
        })()
    JS;
}
