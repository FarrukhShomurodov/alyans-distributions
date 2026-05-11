<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Broadcast extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'title',
        'message',
        'photo_url',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function getStatusNameAttribute(): string
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_SENDING => 'Отправляется',
            self::STATUS_SENT => 'Отправлена',
            self::STATUS_FAILED => 'Ошибка',
        ][$this->status] ?? 'Неизвестно';
    }
}
