<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportChat extends Model
{
    protected $fillable = [
        'bot_user_id',
        'order_id',
        'status',
        'telegram_topic_id',
    ];

    public function user()
    {
        return $this->belongsTo(BotUser::class, 'bot_user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'chat_id')->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(SupportMessage::class, 'chat_id')->latestOfMany();
    }
}
