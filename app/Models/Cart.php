<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use App\Models\PromotionSetting;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'promo_code_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(BotUser::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    protected function applyPercent(float $price, int $percent): float
    {
        $percent = max(0, min(100, $percent));

        return round($price * (100 - $percent) / 100, 2);
    }

    public function pricingSummary(): array
    {
        $this->loadMissing('items.product');

        $promotion = PromotionSetting::query()->first();
        $promotionType = $promotion?->active_type;
        $promotionPercent = (int) ($promotion?->discount_percent ?? 0);

        $items = [];
        $eligibleUnits = [];

        $isOnePlusTwo = $promotionType === PromotionSetting::TYPE_ONE_PLUS_TWO;
        foreach ($this->items as $item) {
            $basePrice = (float) $item->product->price;
            $productDiscount = (int) ($item->product->discount_percent ?? 0);
            $hasProductDiscount = $productDiscount > 0;

            $unitPrice = $basePrice;
            $appliedType = 'none';

            if ($hasProductDiscount) {
                $unitPrice = $this->applyPercent($basePrice, $productDiscount);
                $appliedType = 'product';
            } elseif ($isOnePlusTwo) {
                // Не применяем процентную акцию, если активна 1+2
                $unitPrice = $basePrice;
                $appliedType = 'none';
            } elseif ($promotionType === PromotionSetting::TYPE_PERCENT && $promotionPercent > 0) {
                $unitPrice = $this->applyPercent($basePrice, $promotionPercent);
                $appliedType = 'promo_percent';
            }

            $items[$item->id] = [
                'item_id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => (int) $item->quantity,
                'base_price' => $basePrice,
                'unit_price' => $unitPrice,
                'final_unit_price' => $unitPrice,
                'line_total' => $unitPrice * $item->quantity,
                'product_discount' => $productDiscount,
                'applied_type' => $appliedType,
                'free_qty' => 0,
            ];

            if ($isOnePlusTwo && ! $hasProductDiscount) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $eligibleUnits[] = [
                        'item_id' => $item->id,
                        'unit_price' => $basePrice,
                    ];
                }
            }
        }

        if ($promotionType === PromotionSetting::TYPE_ONE_PLUS_TWO && count($eligibleUnits) > 0) {
            // Группируем по цене
            $byPrice = [];
            foreach ($eligibleUnits as $unit) {
                $byPrice[$unit['unit_price']][] = $unit['item_id'];
            }

            $freeCounts = [];
            foreach ($byPrice as $price => $itemIds) {
                $count = count($itemIds);
                $groupCount = intdiv($count, 3);
                if ($groupCount > 0) {
                    // Для каждой тройки — две бесплатные
                    for ($g = 0; $g < $groupCount; $g++) {
                        for ($i = 0; $i < 3; $i++) {
                            $itemId = $itemIds[$g * 3 + $i];
                            if ($i > 0) { // первые два из тройки бесплатны
                                $freeCounts[$itemId] = ($freeCounts[$itemId] ?? 0) + 1;
                            }
                        }
                    }
                }
            }

            foreach ($items as $itemId => $summary) {
                $freeQty = (int) Arr::get($freeCounts, $itemId, 0);
                if ($freeQty <= 0) {
                    continue;
                }

                $paidQty = max(0, $summary['quantity'] - $freeQty);
                $lineTotal = $summary['unit_price'] * $paidQty;
                $finalUnitPrice = $summary['quantity'] > 0
                    ? round($lineTotal / $summary['quantity'], 2)
                    : 0;

                $items[$itemId]['free_qty'] = $freeQty;
                $items[$itemId]['line_total'] = $lineTotal;
                $items[$itemId]['final_unit_price'] = $finalUnitPrice;
                $items[$itemId]['applied_type'] = 'promo_one_plus_two';
            }
        }

        $total = array_reduce($items, fn($sum, $row) => $sum + $row['line_total'], 0.0);
        $subtotal = array_reduce($items, fn($sum, $row) => $sum + ($row['base_price'] * $row['quantity']), 0.0);
        $discountTotal = max(0, $subtotal - $total);

        // === Скидки по порогам суммы заказа (из ТЗ) ===
        // Настраиваются в admin → promotion_settings.volume_tiers (JSON)
        // По умолчанию: 5000 сум=5%, 10000 сум=7%, 15000 сум=10%, 50000 сум=15%
        $volumeTierPercent = 0;
        $volumeTiers = $promotion?->volume_tiers;
        if (is_string($volumeTiers)) {
            $volumeTiers = json_decode($volumeTiers, true);
        }
        if (!is_array($volumeTiers) || empty($volumeTiers)) {
            $volumeTiers = [
                ['min' => 50000, 'percent' => 15],
                ['min' => 15000, 'percent' => 10],
                ['min' => 10000, 'percent' => 7],
                ['min' => 5000, 'percent' => 5],
            ];
        }
        // Sort descending by min amount
        usort($volumeTiers, fn($a, $b) => $b['min'] <=> $a['min']);
        foreach ($volumeTiers as $tier) {
            if ($total >= $tier['min']) {
                $volumeTierPercent = (int) $tier['percent'];
                break;
            }
        }
        $volumeDiscount = 0;
        if ($volumeTierPercent > 0) {
            $volumeDiscount = round($total * $volumeTierPercent / 100, 2);
            $total = max(0, $total - $volumeDiscount);
            $discountTotal += $volumeDiscount;
        }

        // Применение промокода
        $promoCodeDiscount = 0;
        $promoCode = null;
        if ($this->promo_code_id) {
            $promoCode = $this->promoCode;
            if ($promoCode && $promoCode->isValid((int) $total)) {
                $promoCodeDiscount = $promoCode->calculateDiscount((int) $total);
                $total = max(0, $total - $promoCodeDiscount);
                $discountTotal += $promoCodeDiscount;
            }
        }

        return [
            'promotion_type' => $promotionType,
            'promotion_percent' => $promotionPercent,
            'volume_tier_percent' => $volumeTierPercent,
            'volume_discount' => $volumeDiscount,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total' => $total,
            'promo_code_id' => $promoCode?->id,
            'promo_code' => $promoCode?->code,
            'promo_code_discount' => $promoCodeDiscount,
        ];
    }

    public function totalPrice(): float
    {
        return $this->pricingSummary()['total'];
    }
}
