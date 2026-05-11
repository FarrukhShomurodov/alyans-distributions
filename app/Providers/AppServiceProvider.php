<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        View::composer('admin.layouts.sidebar', function ($view) {
            if (Auth::check()) {
                // Считаем чаты с непрочитанными сообщениями от клиентов
                if (Schema::hasColumn('support_messages', 'read_at')) {
                    $newChatsCount = SupportChat::whereHas('messages', function ($q) {
                        $q->where('is_from_user', true)->whereNull('read_at');
                    })->count();
                } else {
                    // Фолбек на старое поведение, если миграция ещё не запущена
                    $newChatsCount = SupportChat::whereIn('status', ['new', 'open'])->count();
                }
                $view->with('newChatsCount', $newChatsCount);
            }
        });
    }
}
