<?php

use App\Enums\RoomRole;
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

it('lets room owners promote and confirm destructive tile actions', function () {
    [$owner, $participant, $room, , $participantMember] = prepareParticipantModerationRoom();
    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait(3)
        ->assertScript(pmConnectionState(), 'connected');

    $this->actingAs($participant);
    visit($callUrl)
        ->wait(3)
        ->assertScript(pmConnectionState(), 'connected')
        ->assertNoJavaScriptErrors();

    $ownerPage->wait(3)
        ->assertScript(pmTileCount(), 2)
        ->assertScript(pmClickTileAction($participantMember->livekit_identity, 'promote-moderator-participant'), true)
        ->wait(2)
        ->assertNoJavaScriptErrors();

    expect($participantMember->fresh()->role)->toBe(RoomRole::Moderator);

    $ownerPage
        ->assertScript(pmClickTileAction($participantMember->livekit_identity, 'ban-participant'), true)
        ->assertSee('Забанить участника?')
        ->click('@cancel-participant-moderation')
        ->assertScript(pmClickTileAction($participantMember->livekit_identity, 'kick-participant'), true)
        ->assertSee('Удалить участника из звонка?')
        ->click('@confirm-participant-moderation')
        ->wait(2)
        ->assertNoJavaScriptErrors();

    expect($participantMember->fresh()->left_at)->not->toBeNull();
});

it('keeps owner kick and ban tile actions visible after refreshing the call page', function () {
    [$owner, $participant, $room, , $participantMember] = prepareParticipantModerationRoom();
    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait(3)
        ->assertScript(pmConnectionState(), 'connected');

    $this->actingAs($participant);
    visit($callUrl)
        ->wait(3)
        ->assertScript(pmConnectionState(), 'connected')
        ->assertNoJavaScriptErrors();

    $ownerPage->wait(3)
        ->assertScript(pmTileCount(), 2)
        ->assertScript(pmTileActionCount($participantMember->livekit_identity, 'kick-participant'), 1)
        ->assertScript(pmTileActionCount($participantMember->livekit_identity, 'ban-participant'), 1)
        ->refresh()
        ->wait(5)
        ->assertScript(pmConnectionState(), 'connected')
        ->assertScript(pmTileCount(), 2)
        ->assertScript(pmTileActionCount($participantMember->livekit_identity, 'kick-participant'), 1)
        ->assertScript(pmTileActionCount($participantMember->livekit_identity, 'ban-participant'), 1)
        ->assertNoJavaScriptErrors();
});

/**
 * @return array{User, User, Room, RoomMember, RoomMember}
 */
function prepareParticipantModerationRoom(): array
{
    $owner = User::factory()->create([
        'name' => 'Moderation Owner User',
    ]);
    $participant = User::factory()->create([
        'name' => 'Moderation Participant User',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'Participant Moderation Actions Room',
            'waiting_room_enabled' => false,
        ]);

    $ownerMember = RoomMember::factory()
        ->owner()
        ->for($room)
        ->for($owner)
        ->create([
            'display_name' => $owner->name,
        ]);
    $participantMember = RoomMember::factory()
        ->for($room)
        ->for($participant)
        ->create([
            'display_name' => $participant->name,
        ]);

    return [$owner, $participant, $room, $ownerMember, $participantMember];
}

function pmConnectionState(): string
{
    return 'document.querySelector("[data-test=connection-state]").textContent.trim()';
}

function pmTileCount(): string
{
    return 'document.querySelectorAll("[data-test=participant-tile]").length';
}

function pmClickTileAction(string $identity, string $action): string
{
    return '(() => { document.querySelector('.json_encode(pmTileSelector($identity).' [data-test='.$action.']').').click(); return true; })()';
}

function pmTileActionCount(string $identity, string $action): string
{
    return 'document.querySelectorAll('.json_encode(pmTileSelector($identity).' [data-test='.$action.']').').length';
}

function pmTileSelector(string $identity): string
{
    return '[data-test=participant-tile][data-identity="'.$identity.'"]';
}
