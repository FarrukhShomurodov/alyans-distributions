<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionSetting extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_ONE_PLUS_TWO = 'one_plus_two';

    protected $fillable = [
        'active_type',
        'discount_percent',
        'volume_tiers',
    ];

    protected $casts = [
        'volume_tiers' => 'array',
    ];
}
