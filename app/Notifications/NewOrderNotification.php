<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $user = $order->user;

        return (new MailMessage)
            ->subject("Новый заказ #{$order->id} — ALYANS DISTRIBUTIONS")
            ->greeting("Новый заказ #{$order->id}")
            ->line("Сумма: " . number_format($order->total, 0, '', ' ') . " руб.")
            ->line("Клиент: " . ($user ? ($user->first_name . ' ' . $user->second_name) : 'Неизвестен'))
            ->line("Телефон: " . ($order->delivery_phone ?? '—'))
            ->line("Доставка: " . ($order->delivery_address ?? '—'))
            ->line("Оплата: " . $order->payment_name)
            ->action('Открыть заказ', url("/dashboard/orders/{$order->id}"))
            ->line('Данное письмо отправлено автоматически.');
    }
}
