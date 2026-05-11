<?php

namespace App\Http\Controllers\Telegram;

use Illuminate\Contracts\View\View;

class HRMController
{
    public function webapp(): View
    {
        return view('telegram.app');
    }
}
