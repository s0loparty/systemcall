<?php

use App\Models\Room;
use App\Models\User;
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
        ->assertSee('Подготовка к звонку')
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
        ->assertNoJavaScriptErrors();

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
