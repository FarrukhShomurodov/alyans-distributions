<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Models\BotUser;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PromoCode;
use App\Models\StockHistory;
use App\Services\SovaIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Telegram\Bot\Api;

class OrderController
{
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }


    private function getUser(Request $request): BotUser
    {
        $chatId = $request->chat_id ?? $request->header('X-CHAT-ID');

        return BotUser::query()->firstOrCreate([
            'chat_id' => $chatId,
        ]);
    }

    private function t(BotUser $user, string $key, array $replace = []): string
    {
        app()->setLocale($user->lang ?? 'ru');

        return __($key, $replace);
    }

    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'comment'    => 'nullable|string|max:2000',
        ]);

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->load('items.product');

        $pricing = $cart->pricingSummary();

        if ($cart->items->count() === 0) {
            return response()->json([
                'success' => false,
                'msg' => $this->t($user, 'order.cart_empty'),
            ]);
        }

        // Минимальная сумма заказа
        $minOrder = 1500;
        if (($pricing['total'] ?? 0) < $minOrder) {
            return response()->json([
                'success' => false,
                'msg' => "Минимальная сумма заказа — {$minOrder} сум",
            ]);
        }

        // Проверка склада
        $outOfStock = [];
        foreach ($cart->items as $item) {
            $stock = $item->product?->stock;
            $available = $stock ? $stock->quantity : 0;
            if ($available < $item->quantity) {
                $outOfStock[] = $item->product->name . " (доступно: {$available}, в корзине: {$item->quantity})";
            }
        }
        if (!empty($outOfStock)) {
            return response()->json([
                'success' => false,
                'msg' => 'Недостаточно товара на складе: ' . implode(', ', $outOfStock),
            ]);
        }

        $total = (float) ($pricing['total'] ?? 0);

        // === Создание заказа в транзакции ===
        $order = DB::transaction(function () use ($user, $request, $cart, $pricing, $total) {

            $order = Order::create([
                'user_id'             => $user->id,
                'status'              => Order::STATUS_NEW,
                'payment_type'        => 'cash',
                'payment_status'      => Order::PAYMENT_PENDING,
                'total'               => $total,
                'delivery_type'       => 'pickup',
                'delivery_price'      => 0,
                'promo_code_id'       => $pricing['promo_code_id'] ?? null,
                'promo_code_discount' => $pricing['promo_code_discount'] ?? 0,
                'first_name'          => $request->first_name,
                'last_name'           => $request->last_name,
                'patronymic'          => $request->patronymic,
                'email'               => $request->email,
                'phone'               => $request->phone,
                'comment'             => $request->comment,
            ]);

            foreach ($cart->items as $item) {

                $itemSummary = $pricing['items'][$item->id] ?? null;
                $finalUnitPrice = $itemSummary['final_unit_price'] ?? $item->price;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'price'      => $finalUnitPrice,
                    'quantity'   => $item->quantity,
                ]);

                $stock = $item->product->stock;

                if ($stock) {
                    $old = $stock->quantity;
                    $new = max(0, $old - $item->quantity);

                    $stock->update(['quantity' => $new]);

                    StockHistory::create([
                        'stock_id'          => $stock->id,
                        'type'              => 'minus',
                        'quantity'          => $new,
                        'previous_quantity' => $old,
                        'difference'        => $new - $old,
                        'updated_by'        => null,
                        'user_id'           => $user->id,
                        'source'            => 'order',
                        'order_id'          => $order->id,
                    ]);
                }
            }

            if ($cart->promo_code_id) {
                PromoCode::where('id', $cart->promo_code_id)->increment('used_count');
            }

            $cart->items()->delete();
            $cart->update(['promo_code_id' => null]);

            return $order;
        });

        // Сохраняем контактные данные для автозаполнения
        $user->update([
            'first_name'        => $request->first_name ?: $user->first_name,
            'saved_last_name'   => $request->last_name,
            'saved_patronymic'  => $request->patronymic,
            'phone'             => $request->phone ?: $user->phone,
            'saved_email'       => $request->email,
        ]);

        // === Сообщение клиенту в бот ===
        $order->load('items.product');
        $fio = trim(($order->last_name ?? '') . ' ' . ($order->first_name ?? '') . ' ' . ($order->patronymic ?? ''));
        $totalStr = number_format($order->total, 0, '.', ' ');

        $escapeMd = fn($text) => str_replace(['*', '_', '[', ']', '`', '(', ')'], ['\*', '\_', '\[', '\]', '\`', '\(', '\)'], $text);
        $fio = $escapeMd($fio);

        $msg = "({$fio}) Вас приветствует «ALYANS DISTRIBUTIONS»\n";
        $msg .= "Вы оформили заказ № {$order->id}\n";
        if ($order->phone) {
            $msg .= "📞 {$order->phone}\n";
        }
        $msg .= "💰 Сумма – {$totalStr} сум\n\n";
        $msg .= "🛒 *Ваши товары:*\n";

        foreach ($order->items as $oi) {
            $itemTotal = number_format($oi->price * $oi->quantity, 0, '.', ' ');
            $unitPrice = number_format($oi->price, 0, '.', ' ');
            $productName = $escapeMd($oi->product->name ?? 'Товар');
            $msg .= "• {$productName} — {$oi->quantity} × {$unitPrice} = {$itemTotal} сум\n";
        }

        $msg .= "\nМенеджер свяжется с вами для подтверждения заказа.";

        $this->telegram->sendMessage([
            'chat_id'    => $user->chat_id,
            'text'       => $msg,
            'parse_mode' => 'Markdown',
        ]);

        // === Email уведомление ===
        try {
            $emailTo = config('mail.order_notify_to', config('app.order_notification_email', 'orders@alyans-distributions.ru'));
            Mail::raw($this->buildOrderEmailText($order, $user), function ($m) use ($emailTo, $order) {
                $m->to($emailTo)->subject("Новый заказ №{$order->id} — ALYANS DISTRIBUTIONS");
            });
        } catch (\Throwable $e) {
            Log::warning('Order email notification failed: ' . $e->getMessage());
        }

        // === Экспорт в Сову (опционально) ===
        try {
            /** @var SovaIntegrationService $sova */
            $sova = app(SovaIntegrationService::class);
            $result = $sova->exportOrders(true, 1);

            if (!empty($result['error'])) {
                Log::warning("Sova export on create: order #{$order->id} failed", $result);
            } elseif (!empty($result['skipped'])) {
                Log::warning("Sova export on create: order #{$order->id} skipped", [
                    'skipped' => $result['skipped'],
                ]);
            } else {
                Log::info("Sova export on create: order #{$order->id} exported", [
                    'exported' => $result['exported'] ?? 0,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Sova export on create failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'           => true,
            'order_id'          => $order->id,
            'order_total_price' => $order->total,
        ]);
    }

    private function buildOrderEmailText(Order $order, BotUser $user): string
    {
        $fio = trim(($order->last_name ?? '') . ' ' . ($order->first_name ?? '') . ' ' . ($order->patronymic ?? ''));
        $total = number_format($order->total, 0, '.', ' ');

        $text = "Новый заказ №{$order->id}\n";
        $text .= "================================\n\n";
        $text .= "Клиент: {$fio}\n";
        $text .= "Телефон: {$order->phone}\n";
        if ($order->email) {
            $text .= "Email: {$order->email}\n";
        }
        $text .= "Telegram: @{$user->uname} (ID: {$user->chat_id})\n";
        $text .= "\n--- Товары ---\n";

        foreach ($order->items as $oi) {
            $unitPrice = number_format($oi->price, 0, '.', ' ');
            $itemTotal = number_format($oi->price * $oi->quantity, 0, '.', ' ');
            $name = $oi->product->name ?? 'Товар';
            $text .= "{$name} — {$oi->quantity} x {$unitPrice} = {$itemTotal} сум\n";
        }

        $text .= "\nИтого: {$total} сум\n";
        $text .= "Оплата: Наличными при получении\n";

        if ($order->comment) {
            $text .= "\nКомментарий: {$order->comment}\n";
        }

        $text .= "\n================================\n";
        $text .= "Дата заказа: " . $order->created_at->format('d.m.Y H:i') . "\n";

        return $text;
    }
}
