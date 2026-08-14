<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGlobalPinRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_member_id' => ['nullable', 'integer', 'exists:room_members,id'],
        ];
    }

    /**
     * @return array{room_member_id:int|null}
     */
    public function pinData(): array
    {
        $memberId = $this->validated('room_member_id');

        return [
            'room_member_id' => is_numeric($memberId) ? (int) $memberId : null,
        ];
    }
}
