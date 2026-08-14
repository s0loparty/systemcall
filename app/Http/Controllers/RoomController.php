<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\CreateRoomAction;
use App\Actions\Rooms\UpdateRoomAction;
use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Http\Requests\Rooms\StoreRoomRequest;
use App\Http\Requests\Rooms\UpdateRoomRequest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        Gate::forUser($user)->authorize('viewAny', Room::class);

        $rooms = Room::query()
            ->whereBelongsTo($user, 'owner')
            ->orWhereHas('members', fn ($query) => $query
                ->whereBelongsTo($user)
                ->whereNull('left_at'))
            ->latest()
            ->get()
            ->map(fn (Room $room): array => $this->roomPayload($room));

        if (! $request->expectsJson()) {
            return Inertia::render('rooms/Index', [
                'rooms' => $rooms,
            ]);
        }

        return response()->json(['rooms' => $rooms]);
    }

    public function store(StoreRoomRequest $request, CreateRoomAction $createRoom): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $room = $createRoom->handle($user, $request->roomData());

        if (! $request->expectsJson()) {
            return to_route('rooms.call.show', $room);
        }

        return response()->json(['room' => $this->roomPayload($room->load('members'))], 201);
    }

    public function show(Request $request, Room $room): JsonResponse|RedirectResponse
    {
        abort_unless($this->canView($request, $room), 403);

        if (! $request->expectsJson()) {
            return to_route('rooms.call.show', $room);
        }

        return response()->json(['room' => $this->roomPayload($room)]);
    }

    public function update(UpdateRoomRequest $request, Room $room, UpdateRoomAction $updateRoom): JsonResponse
    {
        $room = $updateRoom->handle($room, $request->roomData());

        return response()->json(['room' => $this->roomPayload($room)]);
    }

    public function destroy(Request $request, Room $room): JsonResponse
    {
        Gate::forUser($request->user())->authorize('delete', $room);

        $room->delete();

        return response()->json(status: 204);
    }

    private function canView(Request $request, Room $room): bool
    {
        if ($room->visibility === RoomVisibility::Public && $room->status === RoomStatus::Active) {
            return true;
        }

        $user = $request->user();

        return $user !== null && Gate::forUser($user)->allows('view', $room);
    }

    /**
     * @return array<string, mixed>
     */
    private function roomPayload(Room $room): array
    {
        return [
            'public_id' => $room->public_id,
            'name' => $room->name,
            'description' => $room->description,
            'visibility' => $room->visibility->value,
            'status' => $room->status->value,
            'has_password' => $room->password_hash !== null,
            'waiting_room_enabled' => $room->waiting_room_enabled,
            'max_participants' => $room->max_participants,
            'ended_at' => $room->ended_at?->toISOString(),
        ];
    }
}
