<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\OrdersExport;
use App\Models\Order;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class OrderController
{
    public function index(Request $request): View
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $search = trim((string) $request->query('search', ''));

        $sortBy = in_array($request->query('sort_by'), [
            'id',
            'total',
            'status',
            'payment_status',
            'delivery_type',
            'created_at'
        ]) ? $request->query('sort_by') : 'id';

        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        $orders = Order::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('id', $search)
                        ->orWhere('delivery_phone', 'ILIKE', "%{$search}%")
                        ->orWhere('delivery_address', 'ILIKE', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'ILIKE', "%{$search}%")
                                ->orWhere('second_name', 'ILIKE', "%{$search}%")
                                ->orWhere('phone', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->orderBy($sortBy, $sortDir)
            ->get();

        return view('admin.orders.index', compact('orders', 'dateFrom', 'dateTo', 'search'));
    }

    public function show(Order $order): View
    {
        $chat = null;
        $messages = collect();

        if ($order->user_id) {
            // Чат по конкретному заказу
            $chat = SupportChat::where('bot_user_id', $order->user_id)
                ->where('order_id', $order->id)
                ->first();

            if ($chat) {
                $messages = $chat->messages()->with('admin')->get();
            }
        }

        return view('admin.orders.show', compact('order', 'chat', 'messages'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:new,confirmed,in_process,delivery,done,canceled',
        ]);

        // Если заказ отменен — возвращаем товар на склад
        if ($request->status == 'canceled') {

            foreach ($order->items as $item) {

                $stock = $item->product->stock;

                if (! $stock) {
                    continue; // если на товар нет Stock — пропускаем
                }

                $old = $stock->quantity;
                $returnQty = $item->quantity;

                // Увеличиваем склад
                $stock->quantity += $returnQty;
                $stock->save();

                // Записываем историю: админ вернул товар на склад через отмену заказа из админки
                $stock->history()->create([
                    'type' => 'plus',
                    'quantity' => $returnQty,
                    'previous_quantity' => $old,
                    'difference' => $returnQty,
                    'updated_by' => Auth::id(),
                    'order_id' => $order->id,
                    'source' => 'manual',
                    'user_id' => $order->user_id,
                ]);
            }

        }

        // Для наличных — помечаем оплату как завершённую при завершении заказа
        if ($request->status === 'done') {
            if ($order->payment_type === Order::PAYMENT_CASH && $order->payment_status !== Order::PAYMENT_PAID) {
                $order->update(['payment_status' => Order::PAYMENT_PAID]);
            }
        }

        // Обновляем статус заказа
        $order->update([
            'status' => $request->status,
        ]);

        // Отправляем уведомление пользователю в Telegram
        $this->notifyUserStatusChanged($order);

        return response()->json([
            'success' => true,
            'status' => $order->status,
            'statusName' => $order->status_name,
        ]);
    }

    /**
     * Отправить уведомление пользователю в Telegram о смене статуса заказа.
     */
    private function notifyUserStatusChanged(Order $order): void
    {
        try {
            $user = $order->user;

            if (! $user || ! $user->chat_id) {
                return;
            }

            $lang = $user->lang ?? 'ru';
            app()->setLocale($lang);

            $statusName = __('webapp.order_status_' . $order->status);

            $msg = __('webapp.order_status_changed', ['id' => $order->id]) . "\n";
            $msg .= __('webapp.order_new_status') . ": *{$statusName}*";

            $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
            $telegram->sendMessage([
                'chat_id' => $user->chat_id,
                'text' => $msg,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Не удалось отправить уведомление о статусе заказа: ' . $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new OrdersExport, 'orders.xlsx');
    }

    /**
     * Отправить сообщение клиенту от имени поддержки.
     * Создаёт/использует SupportChat, привязанный к заказу,
     * чтобы клиент мог отвечать из бота и переписка сохранялась.
     */
    public function sendMessage(Request $request, Order $order)
    {
        $request->validate([
            'text' => 'nullable|string|max:4000',
            'attachment' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,zip,rar',
        ]);

        $text = trim((string) $request->input('text', ''));
        if ($text === '' && ! $request->hasFile('attachment')) {
            return back()->with('error', 'Нужно ввести текст или прикрепить файл.');
        }

        $user = $order->user;

        if (! $user || ! $user->chat_id) {
            return back()->with('error', 'У клиента нет Telegram chat_id.');
        }

        // Отдельный чат на каждый заказ
        $chat = SupportChat::firstOrCreate(
            [
                'bot_user_id' => $user->id,
                'order_id' => $order->id,
            ],
            ['status' => 'open']
        );

        $chat->update(['status' => 'open']);

        // Обрабатываем файл (если есть)
        $localUrl = null;
        $fileName = null;
        $fileMime = null;
        $isImage = false;
        $localPath = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileMime = $file->getMimeType();
            $fileName = $file->getClientOriginalName();
            $isImage = str_starts_with((string) $fileMime, 'image/');
            $localPath = $file->store('support_files', 'public');
            $localUrl = asset('storage/' . $localPath);
        }

        // Сохраняем сообщение от админа
        $msgData = [
            'chat_id' => $chat->id,
            'admin_id' => Auth::id(),
            'is_from_user' => false,
            'text' => $text !== '' ? $text : ($isImage ? '📷 Фото' : ($fileName ? '📎 ' . $fileName : '')),
            'photo_url' => $localUrl,
            'source' => 'order',
            'source_order_id' => $order->id,
        ];
        if (\Schema::hasColumn('support_messages', 'file_name') && !$isImage) {
            $msgData['file_name'] = $fileName;
        }
        if (\Schema::hasColumn('support_messages', 'file_mime') && !$isImage) {
            $msgData['file_mime'] = $fileMime;
        }
        SupportMessage::create($msgData);

        // Переводим бота клиента в режим чата
        $user->update([
            'step' => 'chat_with_manager',
            'current_chat_id' => $chat->id,
        ]);

        try {
            $prefix = "💬 *Поддержка ALYANS DISTRIBUTIONS — заказ №{$order->id}*";
            $caption = $prefix . ($text !== '' ? "\n\n" . $text : '');

            $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
            $keyboard = \Telegram\Bot\Keyboard\Keyboard::remove();

            if ($localPath && $isImage) {
                $telegram->sendPhoto([
                    'chat_id' => $user->chat_id,
                    'photo' => \Telegram\Bot\FileUpload\InputFile::create(\Storage::disk('public')->path($localPath), $fileName ?: 'photo.jpg'),
                    'caption' => $caption,
                    'parse_mode' => 'Markdown',
                    'reply_markup' => $keyboard,
                ]);
            } elseif ($localPath) {
                $telegram->sendDocument([
                    'chat_id' => $user->chat_id,
                    'document' => \Telegram\Bot\FileUpload\InputFile::create(\Storage::disk('public')->path($localPath), $fileName ?: 'file'),
                    'caption' => $caption,
                    'parse_mode' => 'Markdown',
                    'reply_markup' => $keyboard,
                ]);
            } else {
                $telegram->sendMessage([
                    'chat_id' => $user->chat_id,
                    'text' => $caption . "\n\n_Можете ответить прямо здесь. Чат закрывает менеджер._",
                    'parse_mode' => 'Markdown',
                    'reply_markup' => $keyboard,
                ]);
            }

            return back()->with('success', 'Сообщение отправлено клиенту в Telegram.');
        } catch (\Throwable $e) {
            Log::warning('Failed to send message to customer: ' . $e->getMessage());

            return back()->with('error', 'Не удалось отправить сообщение: ' . $e->getMessage());
        }
    }

    /**
     * AJAX-поллинг для чата заказа — возвращает новые сообщения.
     */
    public function pollChat(Order $order, Request $request): JsonResponse
    {
        $sinceId = (int) $request->query('since_id', 0);

        $chat = SupportChat::where('bot_user_id', $order->user_id)
            ->where('order_id', $order->id)
            ->first();

        if (! $chat) {
            return response()->json([
                'count' => 0,
                'last_id' => $sinceId,
                'messages_html' => [],
            ]);
        }

        $messages = SupportMessage::where('chat_id', $chat->id)
            ->where('id', '>', $sinceId)
            ->with('admin')
            ->orderBy('created_at')
            ->get();

        // Помечаем новые сообщения от клиента как прочитанные
        SupportMessage::where('chat_id', $chat->id)
            ->where('id', '>', $sinceId)
            ->where('is_from_user', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Рендерим каждое через partial
        $html = [];
        foreach ($messages as $m) {
            $html[] = view('admin.orders._chat_message', [
                'm' => $m,
                'order' => $order,
            ])->render();
        }

        return response()->json([
            'count' => $messages->count(),
            'last_id' => $messages->isNotEmpty() ? $messages->last()->id : $sinceId,
            'messages_html' => $html,
            'chat_status' => $chat->status,
        ]);
    }

    /**
     * Закрыть чат по заказу.
     */
    public function closeChat(Order $order)
    {
        $user = $order->user;

        $chat = SupportChat::where('bot_user_id', $order->user_id)
            ->where('order_id', $order->id)
            ->first();

        if (! $chat) {
            return back()->with('error', 'Чат не найден.');
        }

        $chat->update(['status' => 'closed']);

        if ($user) {
            // Сбрасываем current_chat_id только если он указывал на этот чат
            $updates = ['step' => 'done'];
            if ($user->current_chat_id == $chat->id) {
                $updates['current_chat_id'] = null;
            }
            $user->update($updates);

            try {
                $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

                $menu = Keyboard::make([
                    'keyboard' => [
                        [['text' => '📦 Заказы']],
                        [['text' => '👤 Профиль']],
                        [['text' => '💬 Чат с менеджером']],
                        [['text' => '🛒 Магазин']],
                    ],
                    'resize_keyboard' => true,
                ]);

                $telegram->sendMessage([
                    'chat_id' => $user->chat_id,
                    'text' => "✅ Чат по заказу №{$order->id} завершён. Спасибо за обращение!",
                    'reply_markup' => $menu,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to notify customer about chat close: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Чат закрыт.');
    }
}
