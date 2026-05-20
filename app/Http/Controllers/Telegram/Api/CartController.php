<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Http\Requests\CartAddRequest;
use App\Models\BotUser;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController
{
    protected function getUser(Request $request): BotUser
    {
        return BotUser::query()->firstOrCreate([
            'chat_id' => $request->chat_id,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
        $cart->load('items.product.images');

        $pricing = $cart->pricingSummary();

        return response()->json([
            'items' => $cart->items,
            'total' => $pricing['total'],
            'pricing' => $pricing['items'],
            'subtotal' => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
        ]);
    }

    public function add(CartAddRequest $request): JsonResponse
    {
        $user = $this->getUser($request);
        $product = Product::with('stock')->findOrFail($request->product_id);

        $stock = $product->stock->quantity ?? 0;
        if ($stock < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Нет в наличии',
            ], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        return DB::transaction(function () use ($cart, $product, $stock) {

            $item = CartItem::query()->lockForUpdate()->firstOrCreate(
                ['cart_id' => $cart->id, 'product_id' => $product->id],
                ['price' => $product->price, 'quantity' => 0]
            );

            // Нельзя добавить больше, чем остаток
            if ($item->quantity >= $stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Достигнут лимит по складу',
                ], 422);
            }

            $item->increment('quantity');

            $cart->load('items.product');
            $pricing = $cart->pricingSummary();
            $itemSummary = $pricing['items'][$item->id] ?? null;

            return response()->json([
                'success' => true,
                'item_id' => $item->id,
                'count' => $cart->items()->sum('quantity'),
                'total' => $pricing['total'],
                'subtotal' => $pricing['subtotal'],
                'discount_total' => $pricing['discount_total'],
                'item_total' => $itemSummary['line_total'] ?? null,
                'item_unit_price' => $itemSummary['final_unit_price'] ?? null,
            ]);
        });
    }

    public function update(Request $request): JsonResponse
    {
        $delta = (int) $request->delta;

        return DB::transaction(function () use ($request, $delta) {

            $item = CartItem::lockForUpdate()->findOrFail($request->item_id);
            $product = Product::with('stock')->findOrFail($item->product_id);
            $stock = $product->stock->quantity ?? 0;

            // Увеличение
            if ($delta > 0) {
                if ($item->quantity >= $stock) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Максимум доступного количества',
                    ], 422);
                }

                $item->quantity += 1;
                $item->save();
            }

            // Уменьшение
            if ($delta < 0) {
                if ($item->quantity <= 1) {
                    // удаляем
                    $cartId = $item->cart_id;
                    $item->delete();

                    $cart = Cart::query()->find($cartId);
                    $cart?->load('items.product');
                    $pricing = $cart
                        ? $cart->pricingSummary()
                        : ['total' => 0, 'subtotal' => 0, 'discount_total' => 0, 'items' => []];

                    return response()->json([
                        'success' => true,
                        'quantity' => 0,
                        'total' => $pricing['total'],
                        'subtotal' => $pricing['subtotal'],
                        'discount_total' => $pricing['discount_total'],
                        'count' => CartItem::where('cart_id', $cartId)->sum('quantity'),
                    ]);
                }

                $item->quantity -= 1;
                $item->save();
            }

            $cartId = $item->cart_id;

            $cart = Cart::query()->find($cartId);
            $cart?->load('items.product');
            $pricing = $cart
                ? $cart->pricingSummary()
                : ['total' => 0, 'subtotal' => 0, 'discount_total' => 0, 'items' => []];
            $itemSummary = $pricing['items'][$item->id] ?? null;

            return response()->json([
                'success' => true,
                'quantity' => $item->quantity,
                'total' => $pricing['total'],
                'subtotal' => $pricing['subtotal'],
                'discount_total' => $pricing['discount_total'],
                'count' => CartItem::where('cart_id', $cartId)->sum('quantity'),
                'item_total' => $itemSummary['line_total'] ?? null,
                'item_unit_price' => $itemSummary['final_unit_price'] ?? null,
            ]);
        });
    }

    protected function updateMethod($item, $delta)
    {
        return DB::transaction(function () use ($item, $delta) {
            $item->quantity += $delta;

            if ($item->quantity <= 0) {
                $item->delete();
            } else {
                $item->save();
            }
        });
    }

    public function remove(Request $request): JsonResponse
    {
        $item = CartItem::query()->findOrFail($request->item_id);
        $cartId = $item->cart_id;

        $item->delete();

        $cart = Cart::query()->find($cartId);
        $cart?->load('items.product');
        $pricing = $cart
            ? $cart->pricingSummary()
            : ['total' => 0, 'subtotal' => 0, 'discount_total' => 0, 'items' => []];

        return response()->json([
            'success' => true,
            'message' => 'removed',
            'total' => $pricing['total'],
            'subtotal' => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'count' => CartItem::where('cart_id', $cartId)->sum('quantity'),
        ]);
    }

    public function count(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'count' => $cart->items()->sum('quantity'),
        ]);
    }

    public function applyPromo(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->load('items.product');

        $code = mb_strtoupper(trim($request->input('code', '')));

        if (! $code) {
            return response()->json([
                'success' => false,
                'message' => 'Введите промокод',
            ], 422);
        }

        $promo = PromoCode::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $promo) {
            return response()->json([
                'success' => false,
                'message' => 'Промокод не найден',
            ], 422);
        }

        // Считаем предварительный итог без промокода
        $cart->update(['promo_code_id' => null]);
        $cart->refresh();
        $tempPricing = $cart->pricingSummary();
        $orderAmount = (int) $tempPricing['total'];

        if (! $promo->isValid($orderAmount)) {
            $msg = 'Промокод недействителен';
            if ($promo->min_order_amount > 0 && $orderAmount < $promo->min_order_amount) {
                $min = number_format($promo->min_order_amount, 0, '.', ' ');
                $msg = "Мин. сумма заказа: {$min} сум";
            }
            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 422);
        }

        $cart->update(['promo_code_id' => $promo->id]);
        $cart->refresh();
        $cart->load('items.product', 'promoCode');
        $pricing = $cart->pricingSummary();

        return response()->json([
            'success' => true,
            'message' => 'Промокод применён',
            'promo_code' => $promo->code,
            'promo_discount' => $pricing['promo_code_discount'],
            'total' => $pricing['total'],
            'subtotal' => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'count' => $cart->items()->sum('quantity'),
        ]);
    }

    public function removePromo(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $cart->update(['promo_code_id' => null]);
        $cart->refresh();
        $cart->load('items.product');
        $pricing = $cart->pricingSummary();

        return response()->json([
            'success' => true,
            'total' => $pricing['total'],
            'subtotal' => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'count' => $cart->items()->sum('quantity'),
        ]);
    }

    public function getProductQty(Request $request)
    {
        $user = $this->getUser($request);

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
        $cart->load('items');

        $data = [];

        foreach ($cart->items as $item) {
            $data[] = [
                'product_id' => $item->product_id,
                'item_id' => $item->id,
                'qty' => $item->quantity,
            ];
        }

        return response()->json([
            'items' => $data,
        ]);
    }
}
