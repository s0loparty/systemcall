<?php

use App\Enums\RoomRole;
use App\Events\Rooms\RoomGlobalPinUpdated;
use App\Events\Rooms\RoomMemberHandUpdated;
use App\Events\Rooms\RoomMessageCreated;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomMessage;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

test('active room members can send safe plain text chat messages', function () {
    [$room, $owner, , $participant] = applicationStateRoom();

    Event::fake([RoomMessageCreated::class]);

    $this->actingAs($owner)
        ->postJson(route('rooms.messages.store', $room), [
            'body' => '  <b>Hello room</b>  ',
        ])
        ->assertCreated()
        ->assertJsonPath('message.body', '<b>Hello room</b>')
        ->assertJsonPath('message.room_member_id', $participant->room->members()->whereBelongsTo($owner)->firstOrFail()->id);

    $message = RoomMessage::whereBelongsTo($room)->firstOrFail();

    expect($message->body)->toBe('<b>Hello room</b>')
        ->and($message->type)->toBe('text');

    Event::assertDispatched(RoomMessageCreated::class, function (RoomMessageCreated $event) use ($room): bool {
        $payload = $event->broadcastWith();

        expect($event->broadcastAs())->toBe('room.message.created')
            ->and($event->broadcastOn()[0]->name)->toBe('private-room.'.$room->public_id)
            ->and($payload['message']['body'])->toBe('<b>Hello room</b>');

        return true;
    });

    $this->actingAs($owner)
        ->postJson(route('rooms.messages.store', $room), [
            'body' => str_repeat('a', 1001),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');
});

test('room chat is rate limited per room participant', function () {
    [$room, $owner] = applicationStateRoom();

    for ($message = 1; $message <= 12; $message++) {
        $this->actingAs($owner)
            ->postJson(route('rooms.messages.store', $room), [
                'body' => 'message '.$message,
            ])
            ->assertCreated();
    }

    $this->actingAs($owner)
        ->postJson(route('rooms.messages.store', $room), [
            'body' => 'message 13',
        ])
        ->assertTooManyRequests();
});

test('active room members can raise and lower hands', function () {
    [$room, , , $participant, , , $participantUser] = applicationStateRoom();

    Event::fake([RoomMemberHandUpdated::class]);

    $this->actingAs($participantUser)
        ->patchJson(route('rooms.hand.update', $room), [
            'raised' => true,
        ])
        ->assertOk()
        ->assertJsonPath('member.id', $participant->id)
        ->assertJsonPath('member.hand_raised_at', fn (?string $value): bool => $value !== null);

    expect($participant->fresh()->hand_raised_at)->not->toBeNull();

    Event::assertDispatched(RoomMemberHandUpdated::class);

    $this->actingAs($participantUser)
        ->patchJson(route('rooms.hand.update', $room), [
            'raised' => false,
        ])
        ->assertOk()
        ->assertJsonPath('member.hand_raised_at', null);

    expect($participant->fresh()->hand_raised_at)->toBeNull();
});

test('global pin is synchronized by moderators only', function () {
    [$room, , $moderator, $participant, $moderatorUser, , $participantUser] = applicationStateRoom();

    Event::fake([RoomGlobalPinUpdated::class]);

    $this->actingAs($participantUser)
        ->patchJson(route('rooms.global_pin.update', $room), [
            'room_member_id' => $participant->id,
        ])
        ->assertForbidden();

    $this->actingAs($moderatorUser)
        ->patchJson(route('rooms.global_pin.update', $room), [
            'room_member_id' => $participant->id,
        ])
        ->assertOk()
        ->assertJsonPath('global_pinned_room_member_id', $participant->id);

    expect($room->fresh()->global_pinned_room_member_id)->toBe($participant->id);

    Event::assertDispatched(RoomGlobalPinUpdated::class, function (RoomGlobalPinUpdated $event) use ($room, $moderator, $participant): bool {
        $payload = $event->broadcastWith();

        expect($event->broadcastAs())->toBe('room.global-pin.updated')
            ->and($event->broadcastOn()[0]->name)->toBe('private-room.'.$room->public_id)
            ->and($payload['global_pinned_room_member_id'])->toBe($participant->id)
            ->and($payload['moderator_room_member_id'])->toBe($moderator->id);

        return true;
    });

    $foreignMember = RoomMember::factory()->create();

    $this->actingAs($moderatorUser)
        ->patchJson(route('rooms.global_pin.update', $room), [
            'room_member_id' => $foreignMember->id,
        ])
        ->assertForbidden();

    $this->actingAs($moderatorUser)
        ->patchJson(route('rooms.global_pin.update', $room), [
            'room_member_id' => null,
        ])
        ->assertOk()
        ->assertJsonPath('global_pinned_room_member_id', null);

    expect($room->fresh()->global_pinned_room_member_id)->toBeNull();
});

test('call page exposes application state only to active room members', function () {
    [$room, $owner, , $participant] = applicationStateRoom();
    $stranger = User::factory()->create();

    $message = RoomMessage::factory()
        ->for($room)
        ->for($participant, 'member')
        ->create([
            'body' => 'visible after joining',
        ]);

    $room->update(['global_pinned_room_member_id' => $participant->id]);

    $this->actingAs($stranger)
        ->get(route('rooms.call.show', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Call')
            ->where('room_members', [])
            ->where('room_messages', []));

    $this->actingAs($owner)
        ->get(route('rooms.call.show', $room))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rooms/Call')
            ->where('global_pinned_room_member_id', $participant->id)
            ->has('room_members', 3)
            ->where('room_messages.0.id', $message->id)
            ->where('room_messages.0.body', 'visible after joining'));
});

/**
 * @return array{0:Room,1:User,2:RoomMember,3:RoomMember,4:User,5:RoomMember,6:User}
 */
function applicationStateRoom(): array
{
    $owner = User::factory()->create();
    $moderatorUser = User::factory()->create();
    $participantUser = User::factory()->create();
    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->create();

    $ownerMember = RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->owner()
        ->create();
    $moderator = RoomMember::factory()
        ->for($room)
        ->for($moderatorUser)
        ->moderator()
        ->create();
    $participant = RoomMember::factory()
        ->for($room)
        ->for($participantUser)
        ->create();

    expect($ownerMember->role)->toBe(RoomRole::Owner);

    return [$room, $owner, $moderator, $participant, $moderatorUser, $ownerMember, $participantUser];
}
