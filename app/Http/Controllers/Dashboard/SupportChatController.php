<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Order;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportChatController
{
    public function index(\Illuminate\Http\Request $request): View|Factory|Application
    {
        $status = $request->query('status');     // new | open | closed
        $type = $request->query('type');         // support | order
        $search = trim((string) $request->query('search', ''));
        $orderId = $request->query('order_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $chats = SupportChat::query()
            ->with(['user', 'lastMessage', 'order'])
            // Считаем непрочитанные сообщения от пользователя (is_from_user=true, read_at=null)
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('is_from_user', true)->whereNull('read_at');
            }])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($type === 'support', fn($q) => $q->whereNull('order_id'))
            ->when($type === 'order', fn($q) => $q->whereNotNull('order_id'))
            ->when($orderId, fn($q) => $q->where('order_id', $orderId))
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('second_name', 'ILIKE', "%{$search}%")
                        ->orWhere('uname', 'ILIKE', "%{$search}%")
                        ->orWhere('phone', 'ILIKE', "%{$search}%")
                        ->orWhere('chat_id', 'ILIKE', "%{$search}%");
                });
            })
            ->when($dateFrom, fn($q) => $q->whereDate('updated_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('updated_at', '<=', $dateTo))
            ->orderBy('updated_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.support.index', compact(
            'chats', 'status', 'type', 'search', 'orderId', 'dateFrom', 'dateTo'
        ));
    }

    public function show(SupportChat $chat): View|Factory|Application
    {
        $chat->load(['user', 'messages.admin', 'order']);

        if ($chat->status === 'new') {
            $chat->update(['status' => 'open']);
        }

        // Помечаем все сообщения от клиента в этом чате как прочитанные
        \App\Models\SupportMessage::where('chat_id', $chat->id)
            ->where('is_from_user', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // История заказов клиента
        $orders = Order::where('user_id', $chat->bot_user_id)
            ->with('items.product')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('admin.support.show', compact('chat', 'orders'));
    }

    /**
     * AJAX-эндпоинт для поллинга новых сообщений в открытом чате.
     * Возвращает HTML-блоки сообщений с id > since_id.
     */
    public function poll(SupportChat $chat, Request $request): JsonResponse
    {
        $sinceId = (int) $request->query('since_id', 0);

        $messages = SupportMessage::where('chat_id', $chat->id)
            ->where('id', '>', $sinceId)
            ->with('admin')
            ->orderBy('created_at')
            ->get();

        // Помечаем новые от клиента как прочитанные
        SupportMessage::where('chat_id', $chat->id)
            ->where('id', '>', $sinceId)
            ->where('is_from_user', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Рендерим каждое сообщение через тот же partial что и основная страница
        $html = [];
        foreach ($messages as $msg) {
            $html[] = view('admin.support._message', ['msg' => $msg])->render();
        }

        return response()->json([
            'count' => $messages->count(),
            'last_id' => $messages->isNotEmpty() ? $messages->last()->id : $sinceId,
            'messages_html' => $html,
            'chat_status' => $chat->status,
        ]);
    }

    /**
     * Глобальный счётчик непрочитанных чатов для бейджа в сайдбаре.
     */
    public function unreadCount(): JsonResponse
    {
        $count = SupportChat::whereHas('messages', function ($q) {
            $q->where('is_from_user', true)->whereNull('read_at');
        })->count();

        return response()->json(['count' => $count]);
    }
}
