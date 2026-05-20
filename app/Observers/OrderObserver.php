<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        // Email-уведомления о новых заказах отключены.
        // При необходимости — обработчики событий можно добавить здесь.
    }
}
