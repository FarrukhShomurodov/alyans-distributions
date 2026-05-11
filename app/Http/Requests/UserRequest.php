<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:300',
            'last_name' => 'required|string|max:300',
            'phone_number' => 'required|regex:/^\+?[0-9]{10,}$/',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
