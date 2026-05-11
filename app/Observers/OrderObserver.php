<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\NewOrderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrderObserver
{
    public function created(Order $order): void
    {
        try {
            $email = config('app.order_notification_email', 'orders@alyans-distributions.ru');

            if ($email) {
                Notification::route('mail', $email)
                    ->notify(new NewOrderNotification($order));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send order email notification: ' . $e->getMessage());
        }
    }
}
