<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductService
{
    public function create(array $validated): Model|Product
    {
        return DB::transaction(function () use ($validated) {
            $product = Product::query()->create($validated);

            if (! empty($validated['photos'])) {
                $this->syncPhotos($product, $validated['photos']);
            }

            if (array_key_exists('attributes', $validated)) {
                $this->syncAttributes($product, (array) $validated['attributes']);
            }

            if (! Stock::query()->where('product_id', $product->id)->exists()) {
                $product->stock()->create([
                    'quantity' => 0,
                ]);
            }

            return $product->refresh();
        });
    }

    public function update(Product $product, array $validated): Product
    {
        return DB::transaction(function () use ($product, $validated) {
            $product->update($validated);

            if (! empty($validated['photos'])) {
                $this->syncPhotos($product, $validated['photos']);
            }

            if (array_key_exists('attributes', $validated)) {
                $this->syncAttributes($product, (array) $validated['attributes']);
            }

            return $product->refresh();
        });
    }

    public function delete(Product $product): JsonResponse
    {
        try {
            DB::transaction(function () use ($product) {
                $this->deleteOldPhotos($product);
                $product->delete();
            });

            return response()->json(['message' => 'Продукт успешно удалён'], 204);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Ошибка при удалении продукта',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function syncPhotos(Product $product, array $photos): void
    {
        $total = count($photos);
        $saved = 0;
        $failed = 0;

        Log::info("[product_photos] Начинаем загрузку фото", [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'total_files' => $total,
        ]);

        foreach ($photos as $index => $photo) {
            try {
                if (!$photo || !$photo->isValid()) {
                    Log::warning("[product_photos] Файл невалидный — пропуск", [
                        'product_id' => $product->id,
                        'index' => $index,
                        'error' => $photo ? $photo->getErrorMessage() : 'null',
                    ]);
                    $failed++;
                    continue;
                }

                $originalName = $photo->getClientOriginalName();
                $mime = $photo->getMimeType();
                $size = $photo->getSize();

                Log::info("[product_photos] Фото принято", [
                    'product_id' => $product->id,
                    'index' => $index,
                    'original_name' => $originalName,
                    'mime' => $mime,
                    'size_bytes' => $size,
                    'size_kb' => $size ? round($size / 1024, 2) : null,
                ]);

                // Сохраняем в storage/app/public/products_photos
                $path = $photo->store('products_photos', 'public');

                if (!$path) {
                    Log::error("[product_photos] Не удалось сохранить файл в storage", [
                        'product_id' => $product->id,
                        'original_name' => $originalName,
                    ]);
                    $failed++;
                    continue;
                }

                // Проверяем, что файл реально лежит на диске
                $exists = Storage::disk('public')->exists($path);
                $fullPath = Storage::disk('public')->path($path);

                Log::info("[product_photos] Файл сохранён на диск", [
                    'product_id' => $product->id,
                    'stored_path' => $path,
                    'full_path' => $fullPath,
                    'exists_on_disk' => $exists,
                ]);

                // Создаём запись в БД
                $image = $product->images()->create(['url' => $path]);

                Log::info("[product_photos] Запись в images создана", [
                    'product_id' => $product->id,
                    'image_id' => $image->id,
                    'url' => $image->url,
                    'public_url' => asset('storage/' . $image->url),
                ]);

                $saved++;
            } catch (Throwable $e) {
                $failed++;
                Log::error("[product_photos] Ошибка при сохранении фото", [
                    'product_id' => $product->id,
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info("[product_photos] Загрузка завершена", [
            'product_id' => $product->id,
            'saved' => $saved,
            'failed' => $failed,
            'total' => $total,
        ]);
    }

    protected function deleteOldPhotos(Product $product): void
    {
        Log::info("[product_photos] Удаление всех фото товара", [
            'product_id' => $product->id,
            'count' => $product->images->count(),
        ]);

        foreach ($product->images as $image) {
            try {
                $existsBefore = Storage::disk('public')->exists($image->url);

                if ($existsBefore) {
                    Storage::disk('public')->delete($image->url);
                }

                $imageId = $image->id;
                $url = $image->url;
                $image->delete();

                Log::info("[product_photos] Фото удалено", [
                    'product_id' => $product->id,
                    'image_id' => $imageId,
                    'url' => $url,
                    'file_existed' => $existsBefore,
                ]);
            } catch (Throwable $e) {
                Log::error("[product_photos] Ошибка при удалении фото", [
                    'product_id' => $product->id,
                    'image_id' => $image->id ?? null,
                    'url' => $image->url ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function syncAttributes(Product $product, array $attributes): void
    {
        ProductAttribute::query()->where('product_id', $product->id)->delete();

        $allowedAttributeIds = Attribute::query()
            ->where('category_id', $product->category_id)
            ->whereIn('id', array_keys($attributes))
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (empty($allowedAttributeIds)) {
            return;
        }

        $rows = [];

        foreach ($attributes as $attributeId => $values) {
            if (empty($values) || ! is_array($values)) {
                continue;
            }

            if (! in_array((int) $attributeId, $allowedAttributeIds, true)) {
                continue;
            }

            $validValues = AttributeValue::query()
                ->where('attribute_id', $attributeId)
                ->whereIn('value', array_map('strval', $values))
                ->pluck('value')
                ->all();

            foreach ($validValues as $value) {
                $rows[] = [
                    'product_id' => $product->id,
                    'attribute_id' => $attributeId,
                    'value' => (string) $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($rows)) {
            ProductAttribute::query()->insert($rows);
        }
    }
}
