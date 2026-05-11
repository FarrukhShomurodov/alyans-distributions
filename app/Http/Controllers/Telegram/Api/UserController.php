<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Models\BotUser;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController
{
    protected function getUser(Request $request): BotUser
    {
        return BotUser::firstOrCreate([
            'chat_id' => $request->chat_id,
        ]);
    }

    public function info(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json([
            'user' => [
                'first_name' => $user->first_name,
                'second_name' => $user->second_name,
                'phone' => $user->phone,
                'photo_url' => $request->photo_url ?? null,
            ],
            'orders' => $orders,
        ]);
    }

    public function checkActive(Request $request)
    {
        // Берём chat_id из заголовка ИЛИ из query-параметра (фолбэк)
        $chatId = $request->header('X-CHAT-ID') ?: $request->query('chat_id');

        \Log::info('[check-user]', [
            'header' => $request->header('X-CHAT-ID'),
            'query' => $request->query('chat_id'),
            'resolved' => $chatId,
            'ua' => $request->userAgent(),
        ]);

        if (! $chatId) {
            \Log::warning('[check-user] no chat_id in request');
            return response()->json([
                'active' => false,
                'reason' => 'no_chat_id',
            ]);
        }

        $user = BotUser::query()->where('chat_id', $chatId)->first();

        if (! $user) {
            \Log::warning('[check-user] user not found', ['chat_id' => $chatId]);
            return response()->json([
                'active' => false,
                'reason' => 'user_not_found',
                'chat_id' => $chatId,
            ]);
        }

        if (! $user->is_active) {
            \Log::warning('[check-user] user inactive', [
                'chat_id' => $chatId,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'active' => false,
                'reason' => 'inactive',
                'chat_id' => $chatId,
            ]);
        }

        return response()->json([
            'active' => true,
            'exists' => true,
        ]);
    }
}
