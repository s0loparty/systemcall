<?php

use App\Enums\RoomRole;
use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Enums\WaitingRoomStatus;
use App\Models\Room;
use App\Models\RoomBan;
use App\Models\RoomMember;
use App\Models\RoomMessage;
use App\Models\RoomSubroom;
use App\Models\User;
use App\Models\WaitingRoomMember;
use Illuminate\Support\Facades\Gate;

test('video call domain models persist relationships and casts', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $room = Room::factory()
        ->for($owner, 'owner')
        ->public()
        ->withWaitingRoom()
        ->create();

    $subroom = RoomSubroom::factory()
        ->for($room)
        ->default()
        ->create();

    $ownerMember = RoomMember::factory()
        ->for($room)
        ->for($subroom, 'subroom')
        ->for($owner)
        ->owner()
        ->create();

    $participantMember = RoomMember::factory()
        ->for($room)
        ->for($subroom, 'subroom')
        ->for($participant)
        ->create();

    $message = RoomMessage::factory()
        ->for($room)
        ->for($subroom, 'subroom')
        ->for($participantMember, 'member')
        ->create([
            'metadata' => ['client_id' => 'message-1'],
        ]);

    $waitingMember = WaitingRoomMember::factory()
        ->for($room)
        ->approved()
        ->create();

    $ban = RoomBan::factory()
        ->for($room)
        ->for($participant)
        ->for($owner, 'bannedBy')
        ->temporary()
        ->create();

    expect($room->fresh()->visibility)->toBe(RoomVisibility::Public)
        ->and($room->fresh()->status)->toBe(RoomStatus::Active)
        ->and($room->fresh()->waiting_room_enabled)->toBeTrue()
        ->and($ownerMember->fresh()->role)->toBe(RoomRole::Owner)
        ->and($ownerMember->fresh()->isModerator())->toBeTrue()
        ->and($participantMember->fresh()->role)->toBe(RoomRole::Participant)
        ->and($message->fresh()->metadata)->toBe(['client_id' => 'message-1'])
        ->and($waitingMember->fresh()->status)->toBe(WaitingRoomStatus::Approved)
        ->and($ban->fresh()->expires_at)->not->toBeNull();

    expect($room->owner->is($owner))->toBeTrue()
        ->and($room->members)->toHaveCount(2)
        ->and($room->subrooms)->toHaveCount(1)
        ->and($room->messages)->toHaveCount(1)
        ->and($room->waitingMembers)->toHaveCount(1)
        ->and($room->bans)->toHaveCount(1)
        ->and($subroom->members)->toHaveCount(2)
        ->and($message->member->is($participantMember))->toBeTrue()
        ->and($ban->bannedBy->is($owner))->toBeTrue();

    $room->delete();

    $this->assertModelMissing($subroom);
    $this->assertModelMissing($ownerMember);
    $this->assertModelMissing($participantMember);
    $this->assertModelMissing($message);
    $this->assertModelMissing($waitingMember);
    $this->assertModelMissing($ban);
});

test('room policies enforce owner moderator member boundaries', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create();
    $participant = User::factory()->create();
    $stranger = User::factory()->create();

    $room = Room::factory()
        ->for($owner, 'owner')
        ->create();

    RoomMember::factory()
        ->for($room)
        ->for($owner)
        ->owner()
        ->create();

    RoomMember::factory()
        ->for($room)
        ->for($moderator)
        ->moderator()
        ->create();

    $participantMember = RoomMember::factory()
        ->for($room)
        ->for($participant)
        ->create();

    $message = RoomMessage::factory()
        ->for($room)
        ->for($participantMember, 'member')
        ->create();

    $ban = RoomBan::factory()
        ->for($room)
        ->for($participant)
        ->for($owner, 'bannedBy')
        ->create();

    $waitingMember = WaitingRoomMember::factory()
        ->for($room)
        ->for($participant)
        ->create();

    $subroom = RoomSubroom::factory()
        ->for($room)
        ->create();

    expect(Gate::forUser($owner)->allows('create', Room::class))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $room))->toBeTrue()
        ->and(Gate::forUser($moderator)->allows('update', $room))->toBeFalse()
        ->and(Gate::forUser($participant)->allows('view', $room))->toBeTrue()
        ->and(Gate::forUser($stranger)->allows('view', $room))->toBeFalse()
        ->and(Gate::forUser($moderator)->allows('delete', $participantMember))->toBeTrue()
        ->and(Gate::forUser($participant)->allows('update', $message))->toBeTrue()
        ->and(Gate::forUser($moderator)->allows('delete', $message))->toBeTrue()
        ->and(Gate::forUser($participant)->allows('view', $waitingMember))->toBeTrue()
        ->and(Gate::forUser($moderator)->allows('update', $waitingMember))->toBeTrue()
        ->and(Gate::forUser($moderator)->allows('view', $ban))->toBeTrue()
        ->and(Gate::forUser($moderator)->allows('update', $subroom))->toBeTrue()
        ->and(Gate::forUser($stranger)->allows('view', $subroom))->toBeFalse();
});
