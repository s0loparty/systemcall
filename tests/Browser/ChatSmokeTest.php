<?php

use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomMessage;
use App\Models\User;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('queue.default', 'sync');
    config()->set('services.livekit.url', 'ws://livekit:7880');
    config()->set('services.livekit.api_key', 'devkey');
    config()->set('services.livekit.api_secret', 'local-livekit-secret-local-livekit-secret');
    config()->set('services.livekit.token_ttl', 600);
});

it('sends and receives call chat messages through Reverb without refresh', function () {
    [$owner, $participant, $room] = prepareE9Room();
    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);
    $messageText = 'E9 hello from owner';

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait(3)
        ->assertScript(e9ConnectionState(), 'connected')
        ->assertScript(e9EchoConnectionState(), 'connected');

    $this->actingAs($participant);
    $participantPage = visit($callUrl)
        ->wait(3)
        ->assertScript(e9ConnectionState(), 'connected')
        ->assertScript(e9EchoConnectionState(), 'connected');

    $ownerPage->wait(2)
        ->assertScript(e9TileCount(), 2);

    $participantPage->wait(1)
        ->assertScript(e9TileCount(), 2);

    $this->actingAs($owner);
    $ownerPage->click('@toggle-chat')
        ->wait()
        ->assertScript(e9ChatSidebarCount(), 1)
        ->assertScript(e9ChatMessageCount(), 0)
        ->assertScript(e9SendButtonDisabled(), true)
        ->fill('@chat-message-input', '   ')
        ->assertScript(e9SendButtonDisabled(), true)
        ->fill('@chat-message-input', $messageText)
        ->assertScript(e9SendButtonDisabled(), false)
        ->click('@send-chat-message')
        ->wait(2)
        ->assertSee($messageText)
        ->assertScript(e9ChatMessageCount(), 1)
        ->assertScript(e9ChatInputValue(), '');

    $message = RoomMessage::query()
        ->whereBelongsTo($room)
        ->where('body', $messageText)
        ->firstOrFail();

    $participantPage->click('@toggle-chat')
        ->wait(4)
        ->assertScript(e9ChatSidebarCount(), 1)
        ->assertSee($messageText)
        ->assertScript(e9ChatMessageCount(), 1)
        ->assertScript(e9ChatMessageExists($message->id), 1);

    $participantPage->click('@close-chat')
        ->wait()
        ->assertScript(e9ChatSidebarCount(), 0)
        ->click('@toggle-chat')
        ->wait()
        ->assertScript(e9ChatSidebarCount(), 1)
        ->assertSee($messageText)
        ->assertScript(e9ChatMessageCount(), 1)
        ->assertNoJavaScriptErrors();

    $ownerPage->assertNoJavaScriptErrors();
});

/**
 * @return array{User, User, Room}
 */
function prepareE9Room(): array
{
    $owner = User::factory()->create([
        'name' => 'E9 Owner User',
    ]);
    $participant = User::factory()->create([
        'name' => 'E9 Participant User',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'E9 Chat Room',
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

function e9ConnectionState(): string
{
    return 'document.querySelector("[data-test=connection-state]").textContent.trim()';
}

function e9EchoConnectionState(): string
{
    return 'document.querySelector("[data-test=echo-connection-state]").textContent.replace("Reverb:", "").trim()';
}

function e9TileCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-tile]").length';
}

function e9ChatSidebarCount(): string
{
    return 'document.querySelectorAll("[data-test=chat-sidebar]").length';
}

function e9ChatMessageCount(): string
{
    return 'document.querySelectorAll("[data-test=chat-message]").length';
}

function e9ChatMessageExists(int $messageId): string
{
    return 'document.querySelectorAll('.json_encode('[data-test=chat-message][data-message-id="'.$messageId.'"]').').length';
}

function e9SendButtonDisabled(): string
{
    return 'document.querySelector("[data-test=send-chat-message]").disabled';
}

function e9ChatInputValue(): string
{
    return 'document.querySelector("[data-test=chat-message-input]").value';
}
