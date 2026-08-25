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
        ->assertScript('document.querySelector("[data-test=audio-input-select]").value', '')
        ->assertScript('document.querySelector("[data-test=video-input-select]").value', '')
        ->assertScript('document.querySelector("[data-test=audio-input-select] option:checked").textContent.trim()', 'Без микрофона')
        ->assertScript('document.querySelector("[data-test=video-input-select] option:checked").textContent.trim()', 'Без камеры')
        ->click('Войти в звонок')
        ->assertPathEndsWith('/call')
        ->assertScript('new URL(window.location.href).search', '')
        ->assertNoJavaScriptErrors();
});

it('refreshes fake media devices, auto-selects camera, previews video, and preserves selected devices', function () {
    [$user, $room] = prepareOwnedRoom('E2 Fake Device Room');

    $this->actingAs($user);

    $page = visit(route('rooms.call.prepare', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false))
        ->assertSee('Подготовка к звонку')
        ->assertScript('document.querySelector("[data-test=audio-input-select] option:checked").textContent.trim()', 'Без микрофона')
        ->assertScript('document.querySelector("[data-test=video-input-select] option:checked").textContent.trim()', 'Без камеры');

    $page->wait()
        ->assertScript('[...document.querySelectorAll("[data-test=audio-input-select] option")].map((option) => option.textContent.trim()).includes("E2 Fake Microphone")', true)
        ->assertScript('[...document.querySelectorAll("[data-test=video-input-select] option")].map((option) => option.textContent.trim()).includes("E2 Fake Camera")', true)
        ->assertScript('document.querySelector("[data-test=audio-input-select]").value', '')
        ->assertScript('document.querySelector("[data-test=video-input-select]").value', 'e2-camera')
        ->assertScript('document.querySelectorAll("[data-test=preview-host] video").length', 1)
        ->select('@audio-input-select', 'e2-mic')
        ->wait()
        ->assertScript('document.querySelectorAll("[data-test=preview-host] video").length', 1)
        ->click('Войти в звонок')
        ->assertPathEndsWith('/call')
        ->assertScript('new URLSearchParams(window.location.search).get("audio")', 'e2-mic')
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
        ->assertSee('Подготовка к звонку');

    $page->wait()
        ->assertSee('E2 permission denied')
        ->assertSee('Подготовка к звонку')
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
