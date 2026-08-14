<?php

namespace App\Http\Requests\Rooms;

use App\Enums\RoomVisibility;
use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Room::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::enum(RoomVisibility::class)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'waiting_room_enabled' => ['sometimes', 'boolean'],
            'max_participants' => ['nullable', 'integer', 'min:2', 'max:500'],
        ];
    }

    /**
     * @return array{name:string,description?:string|null,visibility:string,password?:string|null,waiting_room_enabled?:bool,max_participants?:int|null}
     */
    public function roomData(): array
    {
        $data = $this->validated();

        return [
            'name' => (string) $data['name'],
            'description' => isset($data['description']) ? (string) $data['description'] : null,
            'visibility' => (string) $data['visibility'],
            'password' => isset($data['password']) ? (string) $data['password'] : null,
            'waiting_room_enabled' => (bool) ($data['waiting_room_enabled'] ?? false),
            'max_participants' => isset($data['max_participants']) ? (int) $data['max_participants'] : null,
        ];
    }
}
