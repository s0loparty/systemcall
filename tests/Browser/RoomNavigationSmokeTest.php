<?php

use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('services.livekit.url', 'https://livekit.test');
    config()->set('services.livekit.api_key', 'test-key');
    config()->set('services.livekit.api_secret', 'test-secret-test-secret-test-secret');
    config()->set('services.livekit.token_ttl', 600);
});

it('lets guests open shared room preparation links before entering a nickname', function () {
    $room = Room::factory()->public()->create([
        'name' => 'E1 Shared Guest Room',
    ]);

    visit(route('rooms.call.prepare', $room, absolute: false))
        ->assertPathEndsWith('/prepare')
        ->assertSee('E1 Shared Guest Room')
        ->assertSee('Никнейм')
        ->assertPresent('@continue-to-room')
        ->assertNoJavaScriptErrors();
});

it('asks guests for nickname before password on password protected shared links', function () {
    $room = Room::factory()->public()->create([
        'name' => 'E1 Password Guest Room',
        'password_hash' => Hash::make('correct-password'),
    ]);

    visit(route('rooms.call.show', $room, absolute: false))
        ->assertPathEndsWith('/prepare')
        ->assertSee('E1 Password Guest Room')
        ->assertSee('Никнейм')
        ->assertNotPresent('#room-password')
        ->fill('[data-test="guest-display-name"]', 'Password Guest')
        ->click('@continue-to-room')
        ->assertSee('Пароль комнаты')
        ->assertPresent('#room-password')
        ->assertSee('E1 Password Guest Room')
        ->assertNoJavaScriptErrors();
});

it('asks authenticated non owners for password on password protected preparation pages', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create([
        'name' => 'E1 Password Participant',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'E1 Auth Password Room',
            'password_hash' => Hash::make('correct-password'),
        ]);

    $this->actingAs($participant);

    visit(route('rooms.call.prepare', $room, absolute: false))
        ->assertPathEndsWith('/prepare')
        ->assertSee('E1 Auth Password Room')
        ->assertSee('Пароль комнаты')
        ->assertPresent('#room-password')
        ->assertDontSee('Никнейм')
        ->assertNoJavaScriptErrors();
});

it('lets guests enter a nickname and continue from shared room links', function () {
    $room = Room::factory()->public()->create([
        'name' => 'E1 Guest Join Room',
        'waiting_room_enabled' => true,
    ]);

    visit(route('rooms.call.prepare', $room, absolute: false))
        ->assertSee('E1 Guest Join Room')
        ->assertSee('Никнейм')
        ->fill('[data-test="guest-display-name"]', 'Browser Guest')
        ->assertValue('[data-test="guest-display-name"]', 'Browser Guest')
        ->click('@continue-to-room')
        ->assertPathEndsWith('/waiting')
        ->assertSee('Заявка в зал ожидания отправлена')
        ->assertNoJavaScriptErrors();
});

it('requests media devices only after explicit setup button clicks', function () {
    $user = User::factory()->create([
        'name' => 'E1 Device User',
    ]);
    $room = Room::factory()->for($user, 'owner')->public()->create([
        'name' => 'E1 Device Room',
    ]);

    $this->actingAs($user);

    visit(route('rooms.call.prepare', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false))
        ->assertPathEndsWith('/prepare')
        ->assertScript(e1DevicePanelAudioCount(), '0')
        ->assertScript(e1DevicePanelVideoCount(), '0')
        ->click('@request-audio-permission')
        ->wait()
        ->assertScript(e1DevicePanelAudioCount(), '1')
        ->assertScript(e1DevicePanelVideoCount(), '0')
        ->assertScript(e1DevicePanelSelectedAudio(), 'e2-mic')
        ->click('@request-video-permission')
        ->wait()
        ->assertScript(e1DevicePanelVideoCount(), '1')
        ->assertScript(e1DevicePanelSelectedVideo(), 'e2-camera')
        ->assertNoJavaScriptErrors();
});

it('lets an authenticated user create a room and navigate the call flow before media setup', function () {
    $user = User::factory()->create([
        'name' => 'E1 Browser User',
    ]);
    $roomName = 'E1 Browser Room';

    $this->actingAs($user);

    $page = visit(route('rooms.index', absolute: false))
        ->assertSee('Ваши комнаты')
        ->assertSee('Создать комнату')
        ->assertSee('Комнат пока нет')
        ->assertNoJavaScriptErrors();

    $page->fill('name', $roomName)
        ->fill('description', 'Created by the E1 browser smoke test.')
        ->select('visibility', 'public')
        ->click('Создать и войти')
        ->assertPathBeginsWith('/rooms/')
        ->assertPathEndsWith('/prepare')
        ->assertSee('Готовы войти?')
        ->assertSee($roomName)
        ->assertSee('Микрофон')
        ->assertSee('Камера')
        ->assertDontSee('Платформа')
        ->assertNoJavaScriptErrors();

    $room = Room::query()
        ->where('name', $roomName)
        ->firstOrFail();

    $page->navigate(route('rooms.index', absolute: false))
        ->assertSee($roomName)
        ->assertSee('Подготовиться')
        ->assertSourceHas('/rooms/'.$room->public_id.'/prepare')
        ->assertSourceMissing('/rooms/'.$room->public_id.'/call')
        ->click('@delete-room-'.$room->public_id)
        ->assertSee('Удалить комнату?')
        ->click('@confirm-delete-room-'.$room->public_id)
        ->assertPathEndsWith('/rooms')
        ->assertSee('Комнат пока нет')
        ->assertDontSee($roomName)
        ->assertNoJavaScriptErrors();

    $this->assertModelMissing($room);

    $room = Room::factory()
        ->for($user, 'owner')
        ->public()
        ->create([
            'name' => $roomName,
        ]);

    RoomMember::factory()
        ->for($room)
        ->for($user)
        ->owner()
        ->create();

    $this->actingAs($user);

    visit(route('rooms.call.prepare', $room, absolute: false))
        ->assertPathBeginsWith('/rooms/')
        ->assertPathEndsWith('/prepare')
        ->assertSee($roomName)
        ->assertPresent('@continue-to-room')
        ->assertNoJavaScriptErrors();

    $this->actingAs($user)
        ->get(route('rooms.call.show', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Call')
            ->where('room.public_id', $room->public_id)
            ->where('room.name', $roomName));
});

function e1DevicePanelAudioCount(): string
{
    return 'document.querySelector("[data-test=device-setup-panel]").dataset.audioInputCount';
}

function e1DevicePanelVideoCount(): string
{
    return 'document.querySelector("[data-test=device-setup-panel]").dataset.videoInputCount';
}

function e1DevicePanelSelectedAudio(): string
{
    return 'document.querySelector("[data-test=device-setup-panel]").dataset.selectedAudioInputId';
}

function e1DevicePanelSelectedVideo(): string
{
    return 'document.querySelector("[data-test=device-setup-panel]").dataset.selectedVideoInputId';
}
