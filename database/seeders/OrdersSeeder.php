<?php

namespace Database\Seeders;

use App\Models\BotUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('ru_RU');

        // Берём пользователей бота
        $users = BotUser::where('is_active', true)->get();
        if ($users->isEmpty()) {
            $this->command->warn('Нет активных пользователей — заказы не будут созданы.');
            return;
        }

        // Берём продукты
        $products = Product::where('is_active', true)->get();
        if ($products->isEmpty()) {
            $this->command->warn('Нет продуктов — заказы не будут созданы.');
            return;
        }

        $statuses = [
            Order::STATUS_NEW,
            Order::STATUS_CONFIRMED,
            Order::STATUS_PROCESS,
            Order::STATUS_DELIVERY,
            Order::STATUS_DONE,
            Order::STATUS_CANCELED,
        ];

        $paymentTypes = [Order::PAYMENT_CASH, Order::PAYMENT_PAYME];

        for ($i = 0; $i < 30; $i++) {
            $status = $faker->randomElement($statuses);
            $user = $users->random();
            $paymentType = $faker->randomElement($paymentTypes);
            $deliveryType = $faker->randomElement(['pickup', 'delivery']);

            // Логичные статусы оплаты
            $paymentStatus = match ($status) {
                Order::STATUS_DONE => Order::PAYMENT_PAID,
                Order::STATUS_CANCELED => $faker->randomElement([Order::PAYMENT_FAILED, Order::PAYMENT_PENDING]),
                Order::STATUS_DELIVERY, Order::STATUS_CONFIRMED => $faker->randomElement([Order::PAYMENT_PAID, Order::PAYMENT_PENDING]),
                default => $faker->randomElement([Order::PAYMENT_PENDING, Order::PAYMENT_PAID, Order::PAYMENT_FAILED]),
            };

            $createdAt = $faker->dateTimeBetween('-60 days', 'now');

            $order = Order::create([
                'user_id' => $user->id,
                'total' => 0,
                'status' => $status,
                'payment_type' => $paymentType,
                'payment_status' => $paymentStatus,
                'delivery_type' => $deliveryType,
                'delivery_address' => $deliveryType === 'pickup'
                    ? 'Самовывоз'
                    : $faker->address(),
                'delivery_phone' => $user->phone ?? $faker->phoneNumber(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Добавляем 1-5 товаров в заказ
            $total = 0;
            $itemCount = $faker->numberBetween(1, 5);
            $orderProducts = $products->random(min($itemCount, $products->count()));

            foreach ($orderProducts as $product) {
                $quantity = $faker->numberBetween(1, 5);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $quantity,
                ]);
                $total += $product->price * $quantity;
            }

            $order->update(['total' => $total]);
        }

        $this->command->info('Создано 30 тестовых заказов.');
    }
}
