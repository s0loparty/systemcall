<?php

use App\Enums\RoomRole;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomSubroom;
use App\Models\User;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('queue.default', 'sync');
    config()->set('services.livekit.url', 'ws://livekit:7880');
    config()->set('services.livekit.api_key', 'devkey');
    config()->set('services.livekit.api_secret', 'local-livekit-secret-local-livekit-secret');
    config()->set('services.livekit.token_ttl', 600);
});

it('lets moderators create subrooms and move participants from the controls sheet', function () {
    [$owner, $participant, $room, , $participantMember] = prepareSubroomBrowserRoom();
    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait()
        ->assertScript(subroomControlCount(), 1);

    $ownerPage->wait(3)
        ->click('@toggle-subrooms-sheet')
        ->assertSee('Подкомнаты')
        ->fill('@new-subroom-name', 'Команда A')
        ->click('@create-subroom')
        ->wait(2)
        ->assertScript(subroomSheetRoomCount(), 1)
        ->assertScript(subroomMoveButtonCount($participantMember->id), 1)
        ->assertNoJavaScriptErrors();

    $subroom = RoomSubroom::query()
        ->whereBelongsTo($room)
        ->where('name', 'Команда A')
        ->firstOrFail();

    $ownerPage
        ->assertScript(subroomClickMoveButton($participantMember->id, $subroom->public_id), true)
        ->wait(2)
        ->assertNoJavaScriptErrors();

    expect($participantMember->fresh()->room_subroom_id)->toBe($subroom->id);
});

it('shows participant tile action for moving into a subroom', function () {
    [$owner, $participant, $room, , $participantMember] = prepareSubroomBrowserRoom();
    $subroom = RoomSubroom::factory()
        ->for($room)
        ->create([
            'name' => 'Команда B',
            'position' => 1,
        ]);
    $callUrl = route('rooms.call.show', [
        'room' => $room,
        '_e2e_media' => 'fake',
    ], false);

    $this->actingAs($owner);
    $ownerPage = visit($callUrl)
        ->wait(6);

    $this->actingAs($participant);
    visit($callUrl)
        ->wait(3)
        ->assertScript(subroomConnectionState(), 'connected')
        ->assertNoJavaScriptErrors();

    $ownerPage->wait(6)
        ->assertScript(subroomTileActionCount($participantMember->livekit_identity), 1)
        ->assertScript(subroomClickTileMove($participantMember->livekit_identity), true)
        ->wait()
        ->assertScript(subroomClickTileMoveOption($subroom->public_id), true)
        ->wait(2)
        ->assertNoJavaScriptErrors();

    expect($participantMember->fresh()->room_subroom_id)->toBe($subroom->id);
});

/**
 * @return array{User, User, Room, RoomMember, RoomMember}
 */
function prepareSubroomBrowserRoom(): array
{
    $owner = User::factory()->create([
        'name' => 'Subroom Owner User',
    ]);
    $participant = User::factory()->create([
        'name' => 'Subroom Participant User',
    ]);
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create([
            'name' => 'Subroom Browser Room',
            'waiting_room_enabled' => false,
        ]);

    $ownerMember = RoomMember::factory()
        ->owner()
        ->for($room)
        ->for($owner)
        ->create([
            'display_name' => $owner->name,
            'role' => RoomRole::Owner,
        ]);
    $participantMember = RoomMember::factory()
        ->for($room)
        ->for($participant)
        ->create([
            'display_name' => $participant->name,
        ]);

    return [$owner, $participant, $room, $ownerMember, $participantMember];
}

function subroomConnectionState(): string
{
    return 'document.querySelector("[data-test=connection-state]").textContent.trim()';
}

function subroomControlCount(): string
{
    return 'document.querySelectorAll("[data-test=toggle-subrooms-sheet]").length';
}

function subroomSheetRoomCount(): string
{
    return 'document.querySelectorAll("[data-test=subroom-sheet-room]").length';
}

function subroomMoveButtonCount(int $memberId): string
{
    return 'document.querySelectorAll("[data-test=subroom-member-row][data-member-id=\"'.$memberId.'\"] [data-test=move-member-subroom]").length';
}

function subroomClickMoveButton(int $memberId, string $subroomPublicId): string
{
    return '(() => { document.querySelector("[data-test=subroom-member-row][data-member-id=\"'.$memberId.'\"] [data-test=move-member-subroom][data-subroom-id=\"'.$subroomPublicId.'\"]").click(); return true; })()';
}

function subroomTileActionCount(string $identity): string
{
    return 'document.querySelectorAll('.json_encode(subroomTileSelector($identity).' [data-test=move-subroom-participant]').').length';
}

function subroomClickTileMove(string $identity): string
{
    return '(() => { document.querySelector('.json_encode(subroomTileSelector($identity).' [data-test=move-subroom-participant]').').click(); return true; })()';
}

function subroomClickTileMoveOption(string $subroomPublicId): string
{
    return '(() => { document.querySelector("[data-test=move-subroom-option][data-subroom-id=\"'.$subroomPublicId.'\"]").click(); return true; })()';
}

function subroomTileSelector(string $identity): string
{
    return '[data-test=participant-tile][data-identity="'.$identity.'"]';
}
