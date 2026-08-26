<?php

use App\Models\Room;
use App\Models\User;

beforeEach(function () {
    config()->set('services.livekit.url', 'https://livekit.test');
    config()->set('services.livekit.api_key', 'test-key');
    config()->set('services.livekit.api_secret', 'test-secret-test-secret-test-secret');
    config()->set('services.livekit.token_ttl', 600);
});

it('allows continuing from preparation without microphone or camera', function () {
    [$user, $room] = prepareOwnedRoom('E2 No Device Room');

    $this->actingAs($user);

    visit(route('rooms.call.prepare', $room, absolute: false))
        ->assertScript('document.querySelector("[data-test=device-setup-panel]").dataset.selectedAudioInputId', '')
        ->assertScript('document.querySelector("[data-test=device-setup-panel]").dataset.selectedVideoInputId', '')
        ->assertSee('Без микрофона')
        ->assertSee('Без камеры')
        ->click('Войти в звонок')
        ->assertPathEndsWith('/call')
        ->assertScript('new URL(window.location.href).search', '')
        ->assertNoJavaScriptErrors();
});

it('loads fake media devices, auto-selects camera, previews video, and preserves selected devices', function () {
    [$user, $room] = prepareOwnedRoom('E2 Fake Device Room');

    $this->actingAs($user);

    $page = visit(route('rooms.call.prepare', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false))
        ->assertSee('Готовы войти?')
        ->assertSee('Без микрофона');

    $page->wait()
        ->assertScript('document.querySelector("[data-test=device-setup-panel]").dataset.audioInputCount', '1')
        ->assertScript('document.querySelector("[data-test=device-setup-panel]").dataset.videoInputCount', '1')
        ->assertScript('document.querySelector("[data-test=device-setup-panel]").dataset.selectedAudioInputId', '')
        ->assertScript('document.querySelector("[data-test=device-setup-panel]").dataset.selectedVideoInputId', 'e2-camera')
        ->assertScript('document.querySelectorAll("[data-test=preview-host] video").length', 1)
        ->click('Войти в звонок')
        ->assertPathEndsWith('/call')
        ->assertScript('new URLSearchParams(window.location.search).get("audio")', null)
        ->assertScript('new URLSearchParams(window.location.search).get("video")', 'e2-camera')
        ->assertNoJavaScriptErrors();
});

it('shows device permission errors without crashing the preparation page', function () {
    [$user, $room] = prepareOwnedRoom('E2 Permission Error Room');

    $this->actingAs($user);

    $page = visit(route('rooms.call.prepare', [
        'room' => $room,
        '_e2e_media' => 'error',
    ], false))
        ->assertSee('Готовы войти?');

    $page->wait()
        ->assertSee('E2 permission denied')
        ->assertSee('Готовы войти?')
        ->assertNoJavaScriptErrors();
});

/**
 * @return array{User, Room}
 */
function prepareOwnedRoom(string $roomName): array
{
    $user = User::factory()->create();
    $room = Room::factory()
        ->for($user, 'owner')
        ->public()
        ->create([
            'name' => $roomName,
            'waiting_room_enabled' => false,
        ]);

    $room->members()->create([
        'user_id' => $user->id,
        'display_name' => $user->name,
        'role' => 'owner',
        'livekit_identity' => 'room-member:e2-'.str()->uuid(),
        'joined_at' => now(),
    ]);

    return [$user, $room];
}
