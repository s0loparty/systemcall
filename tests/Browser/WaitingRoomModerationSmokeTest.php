<?php

use App\Enums\RoomRole;
use App\Enums\WaitingRoomStatus;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use App\Models\WaitingRoomMember;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('queue.default', 'sync');
    config()->set('services.livekit.url', 'ws://livekit:7880');
    config()->set('services.livekit.api_key', 'devkey');
    config()->set('services.livekit.api_secret', 'local-livekit-secret-local-livekit-secret');
    config()->set('services.livekit.token_ttl', 600);
});

it('approves a waiting participant and moves them into the LiveKit call', function () {
    [$owner, $participant, $room] = prepareE8Room('E8 Approve Waiting Room');
    $prepareUrl = route('rooms.call.prepare', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);
    $moderatorWaitingUrl = route('rooms.call.waiting', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $moderatorPage = visit($moderatorWaitingUrl)
        ->wait(3)
        ->assertScript(e8EchoState(), 'connected')
        ->assertScript(e8PendingCount(), '0')
        ->assertSee('Зал ожидания');

    $this->actingAs($participant);
    $participantPage = visit($prepareUrl)
        ->assertSee('Подготовка к звонку')
        ->click('@refresh-devices')
        ->wait()
        ->select('@audio-input-select', 'e2-mic')
        ->select('@video-input-select', 'e2-camera')
        ->click('@toggle-preview')
        ->wait()
        ->assertScript(e8PreviewVideoCount(), 1)
        ->click('@continue-to-room')
        ->assertPathEndsWith('/waiting')
        ->assertScript(e8WaitingStatus(), WaitingRoomStatus::Pending->value)
        ->assertScript(e8SelectedAudioText(), 'E2 Fake Microphone')
        ->assertScript(e8SelectedVideoText(), 'E2 Fake Camera')
        ->wait()
        ->assertScript(e8PreviewVideoCount(), 1)
        ->assertNoJavaScriptErrors();

    $entry = WaitingRoomMember::query()
        ->whereBelongsTo($room)
        ->whereBelongsTo($participant)
        ->where('status', WaitingRoomStatus::Pending)
        ->firstOrFail();

    $this->actingAs($participant)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();

    $moderatorPage->wait(4)
        ->assertScript(e8PendingCount(), '1')
        ->assertSee($participant->name)
        ->assertScript(e8WaitingEntryCount($entry->id), 1);

    $this->actingAs($owner);
    $moderatorPage->click(e8ApproveSelector($entry->id))
        ->wait(2)
        ->assertScript(e8PendingCount(), '0')
        ->assertScript(e8WaitingEntryCount($entry->id), 0)
        ->assertNoJavaScriptErrors();

    $this->actingAs($participant);
    $participantPage->wait(6)
        ->assertPathEndsWith('/call')
        ->assertScript(e8ConnectionState(), 'connected')
        ->assertNoJavaScriptErrors();

    expect($entry->fresh()->status)->toBe(WaitingRoomStatus::Approved)
        ->and(RoomMember::query()->whereBelongsTo($room)->whereBelongsTo($participant)->exists())->toBeTrue();
});

it('rejects a waiting participant and keeps them out of the LiveKit call', function () {
    [$owner, $participant, $room] = prepareE8Room('E8 Reject Waiting Room');
    $prepareUrl = route('rooms.call.prepare', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);
    $moderatorWaitingUrl = route('rooms.call.waiting', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $moderatorPage = visit($moderatorWaitingUrl)
        ->wait(3)
        ->assertScript(e8EchoState(), 'connected');

    $this->actingAs($participant);
    $participantPage = visit($prepareUrl)
        ->click('@continue-to-room')
        ->assertPathEndsWith('/waiting')
        ->assertScript(e8WaitingStatus(), WaitingRoomStatus::Pending->value);

    $entry = WaitingRoomMember::query()
        ->whereBelongsTo($room)
        ->whereBelongsTo($participant)
        ->where('status', WaitingRoomStatus::Pending)
        ->firstOrFail();

    $moderatorPage->wait(4)
        ->assertScript(e8PendingCount(), '1')
        ->assertScript(e8WaitingEntryCount($entry->id), 1);

    $this->actingAs($owner);
    $moderatorPage->click(e8RejectSelector($entry->id))
        ->wait(2)
        ->assertScript(e8PendingCount(), '0')
        ->assertNoJavaScriptErrors();

    $this->actingAs($participant);
    $participantPage->wait(4)
        ->assertPathEndsWith('/waiting')
        ->assertScript(e8WaitingStatus(), WaitingRoomStatus::Rejected->value)
        ->assertSee('Заявка отклонена')
        ->assertSee('Заявка отклонена. Обратитесь к модератору комнаты.')
        ->assertNoJavaScriptErrors();

    $this->actingAs($participant)
        ->postJson(route('rooms.livekit_token.store', $room))
        ->assertForbidden();

    expect($entry->fresh()->status)->toBe(WaitingRoomStatus::Rejected)
        ->and(RoomMember::query()->whereBelongsTo($room)->whereBelongsTo($participant)->exists())->toBeFalse();
});

/**
 * @return array{User, User, Room}
 */
function prepareE8Room(string $roomName): array
{
    $owner = User::factory()->create([
        'name' => $roomName.' Owner',
    ]);
    $participant = User::factory()->create([
        'name' => $roomName.' Participant',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => $roomName,
            'waiting_room_enabled' => true,
        ]);

    RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->create([
            'display_name' => $owner->name,
            'role' => RoomRole::Owner,
        ]);

    return [$owner, $participant, $room];
}

function e8EchoState(): string
{
    return 'document.querySelector("[data-test=application-realtime-panel]").dataset.echoState';
}

function e8PendingCount(): string
{
    return 'document.querySelector("[data-test=waiting-room-pending-count]").dataset.count';
}

function e8WaitingStatus(): string
{
    return 'document.querySelector("[data-test=waiting-room-status]").dataset.status';
}

function e8SelectedAudioText(): string
{
    return 'document.querySelector("[data-test=selected-audio-device] p:last-child").textContent.trim()';
}

function e8SelectedVideoText(): string
{
    return 'document.querySelector("[data-test=selected-video-device] p:last-child").textContent.trim()';
}

function e8PreviewVideoCount(): string
{
    return 'document.querySelectorAll("[data-test=preview-host] video").length';
}

function e8ConnectionState(): string
{
    return 'document.querySelector("[data-test=connection-state]").textContent.trim()';
}

function e8WaitingEntryCount(int $entryId): string
{
    return 'document.querySelectorAll('.json_encode('[data-test=waiting-room-entry][data-entry-id="'.$entryId.'"]').').length';
}

function e8ApproveSelector(int $entryId): string
{
    return '[data-test=approve-waiting-entry][data-entry-id="'.$entryId.'"]';
}

function e8RejectSelector(int $entryId): string
{
    return '[data-test=reject-waiting-entry][data-entry-id="'.$entryId.'"]';
}
