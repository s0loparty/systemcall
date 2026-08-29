<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class JoinMobileRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
        ];
    }

    public function displayName(): string
    {
        return (string) $this->validated('display_name');
    }
}
