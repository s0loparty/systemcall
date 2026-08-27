<?php

use App\Events\Rooms\RoomMemberLeft;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomSubroom;
use App\Services\LiveKit\InvalidLiveKitWebhookException;
use App\Services\LiveKit\LiveKitWebhookEvent;
use App\Services\LiveKit\LiveKitWebhookReceiver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

function fakeLiveKitWebhook(LiveKitWebhookEvent $event): void
{
    $mock = Mockery::mock(LiveKitWebhookReceiver::class);
    $mock->shouldReceive('receive')
        ->once()
        ->andReturn($event);

    app()->instance(LiveKitWebhookReceiver::class, $mock);
}

function participantLeftWebhookEvent(RoomMember $member, string $roomName, ?string $mediaSessionId = null): LiveKitWebhookEvent
{
    return new LiveKitWebhookEvent(
        event: 'participant_left',
        roomName: $roomName,
        participantIdentity: $member->livekit_identity,
        participantMetadata: [
            'room_member_id' => $member->id,
            'media_session_id' => $mediaSessionId ?? $member->media_session_id,
        ],
    );
}

test('livekit participant left webhook marks the current media session as left', function () {
    Event::fake([RoomMemberLeft::class]);

    $room = Room::factory()->public()->create();
    $member = RoomMember::factory()->for($room)->create([
        'media_session_id' => (string) Str::uuid(),
        'hand_raised_at' => now(),
    ]);
    $room->forceFill(['global_pinned_room_member_id' => $member->id])->save();

    fakeLiveKitWebhook(participantLeftWebhookEvent($member, 'room-'.$room->public_id));

    $this->postJson(route('webhooks.livekit'), ['ignored' => true])
        ->assertNoContent();

    $member->refresh();

    expect($member->left_at)->not->toBeNull()
        ->and($member->hand_raised_at)->toBeNull()
        ->and($room->fresh()->global_pinned_room_member_id)->toBeNull();

    Event::assertDispatchedTimes(RoomMemberLeft::class, 1);
});

test('livekit participant left webhook is idempotent when livekit retries delivery', function () {
    Event::fake([RoomMemberLeft::class]);

    $room = Room::factory()->public()->create();
    $member = RoomMember::factory()->for($room)->create([
        'media_session_id' => (string) Str::uuid(),
    ]);
    $event = participantLeftWebhookEvent($member, 'room-'.$room->public_id);

    fakeLiveKitWebhook($event);
    $this->postJson(route('webhooks.livekit'))->assertNoContent();

    fakeLiveKitWebhook($event);
    $this->postJson(route('webhooks.livekit'))->assertNoContent();

    expect($member->fresh()->left_at)->not->toBeNull();
    Event::assertDispatchedTimes(RoomMemberLeft::class, 1);
});

test('stale participant left webhook cannot close a newer media session', function () {
    Event::fake([RoomMemberLeft::class]);

    $room = Room::factory()->public()->create();
    $member = RoomMember::factory()->for($room)->create([
        'media_session_id' => (string) Str::uuid(),
    ]);
    $oldSessionId = (string) Str::uuid();

    fakeLiveKitWebhook(participantLeftWebhookEvent(
        $member,
        'room-'.$room->public_id,
        $oldSessionId,
    ));

    $this->postJson(route('webhooks.livekit'))->assertNoContent();

    expect($member->fresh()->left_at)->toBeNull();
    Event::assertNotDispatched(RoomMemberLeft::class);
});

test('participant left from a previous livekit room cannot remove a member after subroom switch', function () {
    Event::fake([RoomMemberLeft::class]);

    $room = Room::factory()->public()->create();
    $subroom = RoomSubroom::factory()->for($room)->create();
    $member = RoomMember::factory()->for($room)->create([
        'room_subroom_id' => $subroom->id,
        'media_session_id' => (string) Str::uuid(),
    ]);

    fakeLiveKitWebhook(participantLeftWebhookEvent($member, 'room-'.$room->public_id));

    $this->postJson(route('webhooks.livekit'))->assertNoContent();

    expect($member->fresh()->left_at)->toBeNull();
    Event::assertNotDispatched(RoomMemberLeft::class);
});

test('participant left webhook for the active subroom removes the member', function () {
    Event::fake([RoomMemberLeft::class]);

    $room = Room::factory()->public()->create();
    $subroom = RoomSubroom::factory()->for($room)->create();
    $member = RoomMember::factory()->for($room)->create([
        'room_subroom_id' => $subroom->id,
        'media_session_id' => (string) Str::uuid(),
    ]);

    fakeLiveKitWebhook(participantLeftWebhookEvent($member, $subroom->livekit_room_name));

    $this->postJson(route('webhooks.livekit'))->assertNoContent();

    expect($member->fresh()->left_at)->not->toBeNull();
    Event::assertDispatchedTimes(RoomMemberLeft::class, 1);
});

test('non participant left livekit events do not change membership state', function () {
    Event::fake([RoomMemberLeft::class]);

    $room = Room::factory()->public()->create();
    $member = RoomMember::factory()->for($room)->create([
        'media_session_id' => (string) Str::uuid(),
    ]);

    fakeLiveKitWebhook(new LiveKitWebhookEvent(
        event: 'participant_joined',
        roomName: 'room-'.$room->public_id,
        participantIdentity: $member->livekit_identity,
        participantMetadata: [
            'room_member_id' => $member->id,
            'media_session_id' => $member->media_session_id,
        ],
    ));

    $this->postJson(route('webhooks.livekit'))->assertNoContent();

    expect($member->fresh()->left_at)->toBeNull();
    Event::assertNotDispatched(RoomMemberLeft::class);
});

test('invalid livekit webhook signatures are rejected', function () {
    $this->mock(LiveKitWebhookReceiver::class, function ($mock): void {
        $mock->shouldReceive('receive')
            ->once()
            ->andThrow(new InvalidLiveKitWebhookException('bad signature'));
    });

    $this->postJson(route('webhooks.livekit'))
        ->assertUnauthorized();
});
