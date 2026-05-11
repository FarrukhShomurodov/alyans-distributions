<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'photo_url' => 'nullable|image|mimes:jpg,png',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'discount_percent' => 'nullable|integer|min:0|max:100',
        ];
    }
}
