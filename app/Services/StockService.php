<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function update(Stock $stock, array $validated): Stock
    {
        return DB::transaction(function () use ($stock, $validated) {
            $oldQuantity = $stock->quantity;
            $newQuantity = $validated['quantity'];

            $difference = $newQuantity - $oldQuantity;
            $stock->history()->create([
                'type' => $newQuantity > $oldQuantity ? 'plus' : 'minus',
                'quantity' => abs($newQuantity - $oldQuantity),
                'difference' => $difference,
                'previous_quantity' => $oldQuantity,
                'updated_by' => Auth::id(),
                'source' => 'manual',
            ]);

            $stock->update(['quantity' => $newQuantity]);

            return $stock->refresh();
        });
    }

    /**
     * Синхронизировать остатки из массива импорта.
     *
     * @param array $items массив элементов вида:
     *   ['external_id' => 'SKU-1', 'quantity' => 50]
     *   ['product_id' => 12, 'quantity' => 30]
     * @param string $source метка источника (file|manual)
     * @return array{updated:int, skipped:int, errors:array<int,string>}
     */
    public function syncStocks(array $items, string $source = 'file'): array
    {
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($items as $i => $item) {
            try {
                $quantity = (int) ($item['quantity'] ?? 0);
                $product = null;

                if (!empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                } elseif (!empty($item['external_id'])) {
                    $product = Product::where('external_id', $item['external_id'])->first();
                }

                if (!$product) {
                    $skipped++;
                    continue;
                }

                DB::transaction(function () use ($product, $quantity, $source) {
                    $stock = $product->stock ?? $product->stock()->create(['quantity' => 0]);
                    $old = (int) $stock->quantity;

                    if ($old === $quantity) {
                        return;
                    }

                    $stock->history()->create([
                        'type' => $quantity > $old ? 'plus' : 'minus',
                        'quantity' => abs($quantity - $old),
                        'difference' => $quantity - $old,
                        'previous_quantity' => $old,
                        'updated_by' => Auth::id(),
                        'source' => $source,
                    ]);

                    $stock->update(['quantity' => $quantity]);
                });

                $updated++;
            } catch (\Throwable $e) {
                $errors[] = "Строка #{$i}: " . $e->getMessage();
            }
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
