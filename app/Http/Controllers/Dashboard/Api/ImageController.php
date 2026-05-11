<?php

namespace App\Http\Controllers\Dashboard\Api;

use App\Models\Image;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ImageController
{
    public function deletePhoto(
        string $folderName,
        string $fileName
    ): \Illuminate\Foundation\Application|Response|Application|ResponseFactory {
        $url = $folderName.'/'.$fileName;
        Image::query()->where('url', $url)->delete();
        Storage::disk('public')->delete($url);

        return response([], 204);
    }

    /**
     * Удаление изображения по его ID — более надёжно, чем по URL.
     */
    public function destroy(int $id): JsonResponse
    {
        $image = Image::find($id);

        if (!$image) {
            \Log::warning("[product_photos] Попытка удалить несуществующее изображение", ['image_id' => $id]);
            return response()->json(['success' => false, 'message' => 'Изображение не найдено'], 404);
        }

        $url = $image->url;
        $existsBefore = $url ? Storage::disk('public')->exists($url) : false;

        \Log::info("[product_photos] Удаление фото по API", [
            'image_id' => $id,
            'url' => $url,
            'imageable_type' => $image->imageable_type,
            'imageable_id' => $image->imageable_id,
            'file_existed' => $existsBefore,
        ]);

        try {
            if ($url) {
                Storage::disk('public')->delete($url);
            }
            $image->delete();

            \Log::info("[product_photos] Фото успешно удалено", [
                'image_id' => $id,
                'url' => $url,
            ]);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            \Log::error("[product_photos] Ошибка удаления фото по API", [
                'image_id' => $id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
