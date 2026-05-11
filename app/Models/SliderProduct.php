<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SliderProduct extends Model
{
    protected $fillable = [
        'product_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Получить рандомные товары из слайдера
     */
    public static function getRandomProducts(int $limit = 10)
    {
        return static::where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with('product.images', 'product.category')
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->pluck('product');
    }
}
