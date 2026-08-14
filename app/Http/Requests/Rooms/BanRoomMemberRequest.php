<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BanRoomMemberRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', Rule::date()->after(now())],
        ];
    }

    /**
     * @return array{reason?:string|null,expires_at?:string|null}
     */
    public function banData(): array
    {
        $data = $this->validated();
        $banData = [];

        if (array_key_exists('reason', $data)) {
            $banData['reason'] = isset($data['reason']) ? (string) $data['reason'] : null;
        }

        if (array_key_exists('expires_at', $data)) {
            $banData['expires_at'] = isset($data['expires_at']) ? (string) $data['expires_at'] : null;
        }

        return $banData;
    }
}
