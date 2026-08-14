<?php

namespace App\Http\Requests\Rooms;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomSubroomRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:1', 'max:80'],
        ];
    }

    /**
     * @return array{name:string}
     */
    public function subroomData(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
        ];
    }
}
