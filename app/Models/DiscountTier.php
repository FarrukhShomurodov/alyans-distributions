<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountTier extends Model
{
    protected $fillable = [
        'min_amount',
        'discount_percent',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function getDiscountForAmount(int $amount): int
    {
        return self::where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->orderByDesc('min_amount')
            ->value('discount_percent') ?? 0;
    }
}
