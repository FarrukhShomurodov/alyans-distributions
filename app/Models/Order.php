<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total',
        'status',
        'payment_type',
        'payment_status',
        'delivery_type',
        'delivery_address',
        'delivery_phone',
        'promo_code_id',
        'promo_code_discount',
        'first_name',
        'last_name',
        'patronymic',
        'email',
        'phone',
        'comment',
        'delivery_method',
        'delivery_pvz_code',
        'delivery_pvz_name',
        'delivery_price',
        'delivery_city',
        'delivery_apartment',
        'delivery_floor',
        'delivery_entrance',
        'delivery_intercom',
        'delivery_date',
    ];

    // Статусы
    const STATUS_NEW = 'new';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_PROCESS = 'in_process';

    const STATUS_DELIVERY = 'delivery';

    const STATUS_DONE = 'done';

    const STATUS_CANCELED = 'canceled';

    // Оплата
    const PAYMENT_CASH = 'cash';

    const PAYMENT_PAYME = 'payme';

    const PAYMENT_SBP = 'sbp';

    const PAYMENT_PENDING = 'pending';   // Ожидает оплаты

    const PAYMENT_PAID = 'paid';         // Оплачено

    const PAYMENT_FAILED = 'failed';     // Ошибка оплаты / Не оплачено

    public function getStatusNameAttribute(): string
    {
        return [
            self::STATUS_NEW => 'Новый',
            self::STATUS_CONFIRMED => 'Подтверждён',
            self::STATUS_PROCESS => 'В обработке',
            self::STATUS_DELIVERY => 'Доставка',
            self::STATUS_DONE => 'Завершён',
            self::STATUS_CANCELED => 'Отменён',
        ][$this->status] ?? 'Неизвестно';
    }

    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_NEW => 'Новый',
            self::STATUS_CONFIRMED => 'Подтверждён',
            self::STATUS_PROCESS => 'В обработке',
            self::STATUS_DELIVERY => 'Доставка',
            self::STATUS_DONE => 'Завершён',
            self::STATUS_CANCELED => 'Отменён',
        ];
    }

    public function getPaymentNameAttribute(): string
    {
        return [
            self::PAYMENT_CASH => 'Наличными',
            self::PAYMENT_PAYME => 'Payme',
            self::PAYMENT_SBP => 'СБП',
        ][$this->payment_type] ?? 'Неизвестно';
    }

    public function getPaymentStatusNameAttribute(): string
    {
        return [
            self::PAYMENT_PENDING => 'Ожидает оплаты',
            self::PAYMENT_PAID => 'Оплачено',
            self::PAYMENT_FAILED => 'Ошибка оплаты / Не оплачено',
        ][$this->payment_status] ?? 'Неизвестно';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(BotUser::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @param string[] $fillable
     * @return Order
     */
    public function setFillable(array $fillable): Order
    {
        $this->fillable = $fillable;
        return $this;
    }
}
