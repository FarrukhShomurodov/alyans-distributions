<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string, Password>
     */
    public function rules(): array
    {
        return [
            'login' => 'required|string|unique:admins,login',
            'password' => ['required', 'string', Password::min(8)],
            'role_id' => 'required|integer|exists:roles,id',
        ];
    }
}
