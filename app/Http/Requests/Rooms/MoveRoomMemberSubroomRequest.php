<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Foundation\Http\FormRequest;

class MoveRoomMemberSubroomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
    public function moveData(): array
    {
        return [
            'room_subroom_public_id' => $this->validated('room_subroom_public_id'),
        ];
    }
}
