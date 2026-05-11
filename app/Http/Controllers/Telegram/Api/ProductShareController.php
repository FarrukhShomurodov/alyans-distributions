<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Models\BotUser;
use App\Models\Product;
use App\Models\PromotionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Telegram\Bot\Api;

class ProductShareController
{
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    public function share(Request $request): JsonResponse
    {
        $chatId = $request->input('chat_id');
        $productId = $request->input('product_id');

        if (! $chatId || ! $productId) {
            return response()->json(['success' => false, 'message' => 'Missing params'], 422);
        }

        $product = Product::with(['images', 'category'])->find($productId);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $user = BotUser::where('chat_id', $chatId)->first();
        $lang = $user->lang ?? 'ru';
        app()->setLocale($lang);

        // Расчёт цены
        $promotion = PromotionSetting::query()->first();
        $productDiscount = (int) ($product->discount_percent ?? 0);
        $promoType = $promotion?->active_type ?? null;
        $promoPercent = (int) ($promotion?->discount_percent ?? 0);
        $finalPrice = $product->price;

        if ($productDiscount > 0) {
            $finalPrice = $product->price * (100 - $productDiscount) / 100;
        } elseif ($promoType === PromotionSetting::TYPE_PERCENT && $promoPercent > 0) {
            $finalPrice = $product->price * (100 - $promoPercent) / 100;
        }

        $priceText = number_format($finalPrice, 0, '.', ' ') . ' ₽';
        if ($finalPrice < $product->price) {
            $oldPrice = number_format($product->price, 0, '.', ' ') . ' ₽';
            $priceText .= " (~~{$oldPrice}~~)";
        }

        $caption = "*{$product->name}*\n\n💰 {$priceText}";

        if ($product->category) {
            $caption .= "\n📦 {$product->category->name}";
        }

        $webappUrl = env('WEBAPP_URL') . "/telegram/webapp/product/show/{$product->id}?chat_id={$chatId}";

        $inlineKeyboard = json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => __('bot.more_details', [], $lang) ?: 'Подробнее',
                        'web_app' => ['url' => $webappUrl],
                    ],
                ],
            ],
        ]);

        $image = $product->images->first();

        try {
            if ($image) {
                $imagePath = storage_path('app/public/' . $image->url);

                if (file_exists($imagePath)) {
                    $this->telegram->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => \Telegram\Bot\FileUpload\InputFile::create($imagePath),
                        'caption' => $caption,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => $inlineKeyboard,
                    ]);
                } else {
                    // Попробуем как URL
                    $imageUrl = asset('storage/' . $image->url);
                    $this->telegram->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => $imageUrl,
                        'caption' => $caption,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => $inlineKeyboard,
                    ]);
                }
            } else {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $caption,
                    'parse_mode' => 'Markdown',
                    'reply_markup' => $inlineKeyboard,
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка отправки: ' . $e->getMessage(),
            ], 500);
        }
    }
}
