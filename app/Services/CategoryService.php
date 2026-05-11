<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    public function create(array $validated): Model|Category
    {
        $category = Category::query()->create($validated);

        if (isset($validated['photo_url'])) {
            $photoPath = $validated['photo_url']->store('category_photo', 'public');
            $category->update(['photo_url' => $photoPath]);
        }

        return $category;
    }

    public function update(Category $category, array $validated): Category
    {
        $category->update($validated);

        // Если категория стала неактивной, деактивируем товары
        if (! $category->is_active) {
            $category->products()->where('is_active', true)->update(['is_active' => false]);
        }

        // Если обновили фото
        if (isset($validated['photo_url'])) {
            if ($category->photo_url && Storage::disk('public')->exists($category->photo_url)) {
                Storage::disk('public')->delete($category->photo_url);
            }
            $photoPath = $validated['photo_url']->store('category_photo', 'public');
            $category->update(['photo_url' => $photoPath]);
        }

        // Если обновили скидку и категория конечная (нет детей)
        if (array_key_exists('discount_percent', $validated)) {
            if ($category->children()->count() === 0) {
                // Обновить скидку всем товарам этой категории
                $category->products()->update(['discount_percent' => $validated['discount_percent']]);
            }
        }

        return $category->refresh();
    }

    public function delete(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json([], 204);
    }
}
