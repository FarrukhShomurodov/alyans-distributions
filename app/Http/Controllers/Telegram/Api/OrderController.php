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
            'payment_type' => 'required|in:cash,sbp',
            'delivery_type' => 'required|in:pickup,delivery',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'comment' => 'nullable|string|max:2000',
            'delivery_method' => 'nullable|string|in:cdek_pvz,yandex_pvz,courier',
            'delivery_pvz_code' => 'nullable|string|max:100',
            'delivery_pvz_name' => 'nullable|string|max:500',
            'delivery_price' => 'nullable|numeric|min:0',
            'delivery_city' => 'nullable|string|max:255',
            'delivery_apartment' => 'nullable|string|max:50',
            'delivery_floor' => 'nullable|string|max:20',
            'delivery_entrance' => 'nullable|string|max:20',
            'delivery_intercom' => 'nullable|string|max:50',
            'delivery_date' => 'nullable|date',
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

        // Минимальный заказ 1 500 ₽
        $minOrder = 1500;
        if (($pricing['total'] ?? 0) < $minOrder) {
            return response()->json([
                'success' => false,
                'msg' => "Минимальная сумма заказа — {$minOrder} ₽",
            ]);
        }

        // Final stock check before order confirmation (ТЗ 2.2)
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

        $deliveryPrice = (float) ($request->delivery_price ?? 0);

        // Курьер по Москве: 400 ₽, при заказе от 5 000 ₽ — бесплатно
        if ($request->delivery_method === 'courier') {
            if ($pricing['total'] >= 5000) {
                $deliveryPrice = 0;
            } elseif ($deliveryPrice <= 0) {
                $deliveryPrice = 400;
            }
        }

        // Курьер: запреты на дату доставки
        if ($request->delivery_method === 'courier' && $request->delivery_date) {
            $deliveryTs = strtotime((string) $request->delivery_date);

            // Не дальше чем на 14 дней вперёд
            $maxTs = strtotime('+14 days');
            if ($deliveryTs > $maxTs) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Дату доставки можно выбрать не дальше чем на 2 недели вперёд.',
                ]);
            }

            // В воскресенье доставки нет
            $deliveryDay = (int) date('w', $deliveryTs);
            if ($deliveryDay === 0) {
                return response()->json([
                    'success' => false,
                    'msg' => 'В воскресенье курьерская доставка не осуществляется. Выберите другой день.',
                ]);
            }
        }
        $totalWithDelivery = $pricing['total'] + $deliveryPrice;

        // === Всё в одной транзакции для атомарности ===
        $order = DB::transaction(function () use ($user, $request, $cart, $pricing, $totalWithDelivery, $deliveryPrice) {

            // === Создаём заказ ===
            $order = Order::create([
                'user_id' => $user->id,
                'status' => Order::STATUS_NEW,
                'payment_type' => $request->payment_type,
                'payment_status' => Order::PAYMENT_PENDING,
                'total' => $totalWithDelivery,
                'delivery_type' => $request->delivery_type,
                'delivery_address' => $request->delivery_address,
                'delivery_phone' => $request->delivery_phone ?? $request->phone,
                'promo_code_id' => $pricing['promo_code_id'] ?? null,
                'promo_code_discount' => $pricing['promo_code_discount'] ?? 0,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'patronymic' => $request->patronymic,
                'email' => $request->email,
                'phone' => $request->phone,
                'comment' => $request->comment,
                'delivery_method' => $request->delivery_method,
                'delivery_pvz_code' => $request->delivery_pvz_code,
                'delivery_pvz_name' => $request->delivery_pvz_name,
                'delivery_price' => $deliveryPrice,
                'delivery_city' => $request->delivery_city,
                'delivery_apartment' => $request->delivery_apartment,
                'delivery_floor' => $request->delivery_floor,
                'delivery_entrance' => $request->delivery_entrance,
                'delivery_intercom' => $request->delivery_intercom,
                'delivery_date' => $request->delivery_date,
            ]);

            // === Позиции заказа + списание стока ===
            foreach ($cart->items as $item) {

                $itemSummary = $pricing['items'][$item->id] ?? null;
                $finalUnitPrice = $itemSummary['final_unit_price'] ?? $item->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'price' => $finalUnitPrice,
                    'quantity' => $item->quantity,
                ]);

                $stock = $item->product->stock;

                if ($stock) {
                    $old = $stock->quantity;
                    $new = max(0, $old - $item->quantity);

                    $stock->update(['quantity' => $new]);

                    StockHistory::create([
                        'stock_id' => $stock->id,
                        'type' => 'minus',
                        'quantity' => $new,
                        'previous_quantity' => $old,
                        'difference' => $new - $old,
                        'updated_by' => null,
                        'user_id' => $user->id,
                        'source' => 'order',
                        'order_id' => $order->id,
                    ]);
                }
            }

            // === Инкремент счётчика промокода ===
            if ($cart->promo_code_id) {
                PromoCode::where('id', $cart->promo_code_id)->increment('used_count');
            }

            // === Очистка корзины ===
            $cart->items()->delete();
            $cart->update(['promo_code_id' => null]);

            return $order;
        });

        // === Сохраняем данные доставки для автозаполнения ===
        $user->update([
            'first_name' => $request->first_name ?: $user->first_name,
            'saved_last_name' => $request->last_name,
            'saved_patronymic' => $request->patronymic,
            'phone' => $request->phone ?: $user->phone,
            'saved_email' => $request->email,
            'saved_delivery_address' => $request->delivery_address,
            'saved_delivery_city' => $request->delivery_city,
            'saved_delivery_method' => $request->delivery_method,
            'saved_delivery_apartment' => $request->delivery_apartment,
            'saved_delivery_floor' => $request->delivery_floor,
            'saved_delivery_entrance' => $request->delivery_entrance,
            'saved_delivery_intercom' => $request->delivery_intercom,
        ]);

        // === Сообщение пользователю (формат из ТЗ) ===
        $order->load('items.product');
        $fio = trim(($order->last_name ?? '') . ' ' . ($order->first_name ?? '') . ' ' . ($order->patronymic ?? ''));
        $total = number_format($order->total, 0, '.', ' ');

        // Функция экранирования спецсимволов Markdown
        $escapeMd = fn($text) => str_replace(['*', '_', '[', ']', '`', '(', ')'], ['\*', '\_', '\[', '\]', '\`', '\(', '\)'], $text);

        $fio = $escapeMd($fio);
        $msg = "({$fio}) Вас приветствует «ALYANS DISTRIBUTIONS»\n";
        $msg .= "Вы оформили заказ № {$order->id}\n";
        if ($order->phone) {
            $msg .= "📞 {$order->phone}\n";
        }
        $msg .= "💰 Сумма – {$total} ₽\n\n";
        $msg .= "🛒 *Ваши товары:*\n";

        foreach ($order->items as $oi) {
            $itemTotal = number_format($oi->price * $oi->quantity, 0, '.', ' ');
            $unitPrice = number_format($oi->price, 0, '.', ' ');
            $productName = $escapeMd($oi->product->name ?? 'Товар');
            $msg .= "• {$productName} — {$oi->quantity} × {$unitPrice} = {$itemTotal} ₽\n";
        }

        // Delivery info
        $deliveryMethodNames = [
            'cdek_pvz' => 'СДЭК ПВЗ',
            'yandex_pvz' => 'Яндекс Доставка ПВЗ',
            'courier' => 'Курьером',
        ];
        $deliveryName = $deliveryMethodNames[$order->delivery_method] ?? 'Доставка';
        $deliveryAddress = $escapeMd($order->delivery_address ?? '');
        $msg .= "\n📦 *Адрес доставки:* {$deliveryName}";
        if ($deliveryAddress) {
            $msg .= ", {$deliveryAddress}";
        }
        $msg .= "\n";

        // Стоимость доставки
        if ($order->delivery_method === 'courier') {
            $courierCost = (int) $order->delivery_price;
            if ($courierCost > 0) {
                $msg .= "🚚 Доставка внутри МКАД: {$courierCost} ₽\n";
                $msg .= "💡 При заказе от 5 000 ₽ — доставка бесплатно\n";
            } else {
                $msg .= "🚚 Доставка внутри МКАД: Бесплатно\n";
            }
        } elseif (in_array($order->delivery_method, ['cdek_pvz', 'yandex_pvz'], true)) {
            // ТК (СДЭК/Яндекс) — пишем "Доставка ТК" + ориентировочная цена если есть
            if ($order->delivery_price > 0) {
                $deliveryCost = number_format($order->delivery_price, 0, '.', ' ');
                $msg .= "🚚 Доставка ТК: ~{$deliveryCost} ₽\n";
            } else {
                $msg .= "🚚 Доставка ТК\n";
            }
        } elseif ($order->delivery_price > 0) {
            $deliveryCost = number_format($order->delivery_price, 0, '.', ' ');
            $msg .= "🚚 Доставка: ~{$deliveryCost} ₽\n";
        }

            $msg .= "\n*🤔 Все верно?*";

        $this->telegram->sendMessage([
            'chat_id' => $user->chat_id,
            'text' => $msg,
            'parse_mode' => 'Markdown',
        ]);

        // === Email уведомление ===
        try {
            $emailTo = config('mail.order_notify_to', 'uamerike@gmail.com');
            $order->load('items.product');
            Mail::raw($this->buildOrderEmailText($order, $user), function ($m) use ($emailTo, $order) {
                $m->to($emailTo)
                    ->subject("Новый заказ №{$order->id} — ALYANS DISTRIBUTIONS");
            });
        } catch (\Throwable $e) {
            Log::warning('Order email notification failed: ' . $e->getMessage());
        }

        // === Отправка заказа в Сову сразу при создании ===
        try {
            /** @var SovaIntegrationService $sova */
            $sova = app(SovaIntegrationService::class);
            $result = $sova->exportOrders(true, 1); // только новые, лимит 1 (наш только что созданный)

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
            'success' => true,
            'order_id' => $order->id,
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
            $text .= "{$name} — {$oi->quantity} x {$unitPrice} = {$itemTotal} ₽\n";
        }

        $text .= "\nИтого: {$total} ₽\n";

        $deliveryMethodNames = [
            'cdek_pvz' => 'СДЭК ПВЗ',
            'yandex_pvz' => 'Яндекс Доставка ПВЗ',
            'courier' => 'Курьером',
        ];
        $deliveryName = $deliveryMethodNames[$order->delivery_method] ?? 'Доставка';
        $text .= "\n--- Доставка ---\n";
        $text .= "Способ: {$deliveryName}\n";
        if ($order->delivery_address) {
            $text .= "Адрес: {$order->delivery_address}\n";
        }
        if ($order->delivery_city) {
            $text .= "Город: {$order->delivery_city}\n";
        }
        if ($order->delivery_apartment) {
            $text .= "Кв: {$order->delivery_apartment}";
            if ($order->delivery_floor) $text .= ", этаж: {$order->delivery_floor}";
            if ($order->delivery_entrance) $text .= ", подъезд: {$order->delivery_entrance}";
            if ($order->delivery_intercom) $text .= ", домофон: {$order->delivery_intercom}";
            $text .= "\n";
        }
        if ($order->delivery_method === 'courier') {
            if ($order->delivery_price > 0) {
                $text .= "Доставка внутри МКАД: " . number_format($order->delivery_price, 0, '.', ' ') . " ₽\n";
            } else {
                $text .= "Доставка внутри МКАД: Бесплатно\n";
            }
        } elseif ($order->delivery_price > 0) {
            $text .= "Стоимость доставки: ~" . number_format($order->delivery_price, 0, '.', ' ') . " ₽\n";
        }
        if ($order->delivery_date) {
            $text .= "Дата доставки: {$order->delivery_date}\n";
        }

        $paymentNames = ['cash' => 'Наличными при получении', 'sbp' => 'СБП (100% предоплата)'];
        $text .= "\nОплата: " . ($paymentNames[$order->payment_type] ?? $order->payment_type) . "\n";
        if ($order->comment) {
            $text .= "\nКомментарий: {$order->comment}\n";
        }

        $text .= "\n================================\n";
        $text .= "Дата заказа: " . $order->created_at->format('d.m.Y H:i') . "\n";

        return $text;
    }
}
