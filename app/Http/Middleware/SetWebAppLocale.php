<?php

namespace App\Http\Middleware;

use App\Models\BotUser;
use Closure;
use Illuminate\Http\Request;

class SetWebAppLocale
{
    public function handle(Request $request, Closure $next)
    {
        $chatId = $request->query('chat_id')
            ?? $request->header('X-CHAT-ID');

        if ($chatId) {
            $user = BotUser::query()->where('chat_id', $chatId)->first();

            if ($user && $user->lang) {
                app()->setLocale($user->lang);
            }
        }

        return $next($request);
    }
}
