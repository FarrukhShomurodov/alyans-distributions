<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'paycom_transaction_id',
        'paycom_time',
        'paycom_time_datetime',
        'create_time',
        'perform_time',
        'cancel_time',
        'amount',
        'state',
        'reason',
        'receivers',
        'order_id',
        'perform_time_unix',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getByTimeRange($from, $to): Collection|array
    {
        return self::query()->whereBetween('state', [$from, $to])->get();
    }
}
