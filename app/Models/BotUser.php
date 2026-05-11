<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotUser extends Model
{
    protected $fillable = [
        'chat_id',
        'first_name',
        'second_name',
        'uname',
        'phone',
        'step',
        'current_chat_id',
        'is_active',
        'lang',
        'saved_last_name',
        'saved_patronymic',
        'saved_email',
        'saved_delivery_address',
        'saved_delivery_city',
        'saved_delivery_method',
        'saved_delivery_apartment',
        'saved_delivery_floor',
        'saved_delivery_entrance',
        'saved_delivery_intercom',
    ];

    protected $attributes = [
        'lang' => null,
        'step' => 'start',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
