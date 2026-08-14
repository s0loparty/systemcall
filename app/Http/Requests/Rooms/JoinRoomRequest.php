<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JoinRoomRequest extends FormRequest
{
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
            'display_name' => [
                Rule::requiredIf(fn (): bool => $this->user() === null),
                'nullable',
                'string',
                'max:120',
            ],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{display_name?:string|null,password?:string|null}
     */
    public function joinData(): array
    {
        $data = $this->validated();
        $joinData = [];

        if (array_key_exists('display_name', $data)) {
            $joinData['display_name'] = isset($data['display_name']) ? (string) $data['display_name'] : null;
        }

        if (array_key_exists('password', $data)) {
            $joinData['password'] = isset($data['password']) ? (string) $data['password'] : null;
        }

        return $joinData;
    }
}
