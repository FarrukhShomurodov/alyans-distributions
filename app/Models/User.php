<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasUlids;
    use Notifiable;

    protected $fillable = [
        'login',
        'password',
    ];

    protected $hidden = ['password'];
}
