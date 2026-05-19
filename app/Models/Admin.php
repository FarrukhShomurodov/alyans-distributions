<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasRoles;

    protected $guard_name  = 'admin';
    protected $guarded  = 'admin';


    protected $fillable = [
        'login',
        'password',
        'telegram_user_id',
        'telegram_username',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'password' => 'string',
    ];

    protected $hidden = ['password'];
}
