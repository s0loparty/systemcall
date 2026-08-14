<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Foundation\Http\FormRequest;

class SwitchRoomSubroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'room_subroom_public_id' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array{room_subroom_public_id:string|null}
     */
    public function switchData(): array
    {
        return [
            'room_subroom_public_id' => $this->validated('room_subroom_public_id'),
        ];
    }
}
