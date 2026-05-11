<?php

namespace App\Services;

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
}
