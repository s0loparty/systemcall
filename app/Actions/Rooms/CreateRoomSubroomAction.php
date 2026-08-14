<?php

namespace App\Actions\Rooms;

use App\Events\Rooms\RoomSubroomCreated;
use App\Models\Room;
use App\Models\RoomSubroom;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateRoomSubroomAction
{
    private const MaxSubrooms = 5;

    public function __construct(private FindAuthorizedRoomMemberAction $findMember) {}

    /**
     * @param  array{name:string}  $data
     */
    public function handle(Room $room, ?User $user, ?string $guestId, array $data): RoomSubroom
    {
        $actor = $this->findMember->handle($room, $user, $guestId);

        if (! $actor->isModerator()) {
            throw new AuthorizationException('Only room moderators may create subrooms.');
        }

        $subroom = DB::transaction(function () use ($room, $data): RoomSubroom {
            Room::whereKey($room->id)->lockForUpdate()->firstOrFail();

            $currentCount = $room->subrooms()->count();

            if ($currentCount >= self::MaxSubrooms) {
                throw ValidationException::withMessages([
                    'name' => 'A room can have at most five subrooms.',
                ]);
            }

            $publicId = $this->uniquePublicId();

            return $room->subrooms()->create([
                'public_id' => $publicId,
                'name' => trim($data['name']),
                'livekit_room_name' => 'room-'.$room->public_id.'-subroom-'.$publicId,
                'position' => $currentCount + 1,
            ]);
        });

        $subroom->setRelation('room', $room);

        RoomSubroomCreated::dispatch($subroom);

        return $subroom;
    }

    private function uniquePublicId(): string
    {
        do {
            $publicId = Str::lower(Str::random(16));
        } while (RoomSubroom::where('public_id', $publicId)->exists());

        return $publicId;
    }
}
