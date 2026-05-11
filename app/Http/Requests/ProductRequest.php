<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'external_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'external_id')->ignore($productId),
            ],
            'photos' => 'sometimes|array|max:10',
            'photos.*' => 'sometimes|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
            'attributes' => 'sometimes|array',
            'attributes.*' => 'nullable|array',
            'attributes.*.*' => 'nullable|string|max:255',
        ];
    }
}
