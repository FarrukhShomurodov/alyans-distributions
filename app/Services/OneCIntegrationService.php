<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OneCIntegrationService
{
    public function getOrdersForExport(?string $status, bool $onlyNotExported, int $limit): Collection
    {
        return Order::query()
            ->with(['user', 'items.product'])
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($onlyNotExported, fn($query) => $query->whereNull('one_c_exported_at'))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function mapOrderForOneC(Order $order): array
    {
        $customer = $order->user;

        return [
            'id' => $order->id,
            'created_at' => optional($order->created_at)?->toIso8601String(),
            'status' => $order->status,
            'payment_type' => $order->payment_type,
            'payment_status' => $order->payment_status,
            'total' => (float) $order->total,
            'delivery_phone' => $order->delivery_phone,
            'delivery_address' => $order->delivery_address,
            'customer' => [
                'id' => $customer?->id,
                'first_name' => $customer?->first_name,
                'second_name' => $customer?->second_name,
                'phone' => $customer?->phone,
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_external_id' => $item->product?->external_id,
                    'product_name' => $item->product?->name,
                    'quantity' => (int) $item->quantity,
                    'price' => (float) $item->price,
                    'line_total' => (float) $item->price * (int) $item->quantity,
                ];
            })->values()->all(),
        ];
    }

    public function markOrdersExported(array $orderIds): int
    {
        $ids = collect($orderIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        return Order::query()
            ->whereIn('id', $ids)
            ->update(['one_c_exported_at' => Carbon::now()]);
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        $order->update([
            'status' => $status,
        ]);

        return $order->refresh();
    }

    public function syncStocks(array $items, string $source = '1c'): array
    {
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($items, $source, &$updated, &$skipped, &$errors) {
            foreach ($items as $index => $item) {
                $quantity = Arr::get($item, 'quantity');
                $externalId = Arr::get($item, 'external_id');
                $productId = Arr::get($item, 'product_id');

                if (!is_numeric($quantity) || (int) $quantity < 0) {
                    $skipped++;
                    $errors[] = [
                        'index' => $index,
                        'reason' => 'Invalid quantity',
                    ];
                    continue;
                }

                if (!$externalId && !$productId) {
                    $skipped++;
                    $errors[] = [
                        'index' => $index,
                        'reason' => 'Either external_id or product_id is required',
                    ];
                    continue;
                }

                $productQuery = Product::query();

                if ($externalId) {
                    $productQuery->where('external_id', $externalId);
                } else {
                    $productQuery->whereKey((int) $productId);
                }

                $product = $productQuery->first();

                if (!$product) {
                    $skipped++;
                    $errors[] = [
                        'index' => $index,
                        'reason' => 'Product not found',
                    ];
                    continue;
                }

                $stock = $product->stock()->firstOrCreate([], ['quantity' => 0]);

                $oldQuantity = (int) $stock->quantity;
                $newQuantity = (int) $quantity;

                if ($oldQuantity === $newQuantity) {
                    $skipped++;
                    continue;
                }

                $difference = $newQuantity - $oldQuantity;

                $stock->history()->create([
                    'type' => $difference >= 0 ? 'plus' : 'minus',
                    'quantity' => abs($difference),
                    'difference' => $difference,
                    'previous_quantity' => $oldQuantity,
                    'updated_by' => null,
                    'source' => $source,
                ]);

                $stock->update(['quantity' => $newQuantity]);
                $updated++;
            }
        });

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function validateStatus(string $status): void
    {
        $allowed = [
            Order::STATUS_NEW,
            Order::STATUS_PROCESS,
            Order::STATUS_DONE,
            Order::STATUS_CANCELED,
        ];

        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid order status.');
        }
    }
}
