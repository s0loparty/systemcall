<?php

namespace App\Http\Requests\Rooms;

use App\Enums\RoomVisibility;
use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $room = $this->route('room');

        return $room instanceof Room
            && ($this->user()?->can('update', $room) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['sometimes', 'required', Rule::enum(RoomVisibility::class)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'waiting_room_enabled' => ['sometimes', 'boolean'],
            'max_participants' => ['nullable', 'integer', 'min:2', 'max:500'],
        ];
    }

    /**
     * @return array{name?:string,description?:string|null,visibility?:string,password?:string|null,waiting_room_enabled?:bool,max_participants?:int|null}
     */
    public function roomData(): array
    {
        $data = $this->validated();
        $roomData = [];

        if (array_key_exists('name', $data)) {
            $roomData['name'] = (string) $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $roomData['description'] = isset($data['description']) ? (string) $data['description'] : null;
        }

        if (array_key_exists('visibility', $data)) {
            $roomData['visibility'] = (string) $data['visibility'];
        }

        if (array_key_exists('password', $data)) {
            $roomData['password'] = isset($data['password']) ? (string) $data['password'] : null;
        }

        if (array_key_exists('waiting_room_enabled', $data)) {
            $roomData['waiting_room_enabled'] = (bool) $data['waiting_room_enabled'];
        }

        if (array_key_exists('max_participants', $data)) {
            $roomData['max_participants'] = isset($data['max_participants']) ? (int) $data['max_participants'] : null;
        }

        return $roomData;
    }
}
