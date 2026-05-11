<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $fillable = [
        'chat_id',
        'admin_id',
        'is_from_user',
        'text',
        'read_at',
        'photo_url',
        'file_name',
        'file_mime',
        'source',
        'source_order_id',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_from_user' => 'boolean',
    ];

    public function chat()
    {
        return $this->belongsTo(SupportChat::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
