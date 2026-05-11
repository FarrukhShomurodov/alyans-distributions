<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class PromoCodesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('ru_RU');

        $promoCodes = [
            [
                'code' => 'WELCOME10',
                'description' => 'Скидка 10% для новых клиентов',
                'type' => PromoCode::TYPE_PERCENT,
                'value' => 10,
                'min_order_amount' => 2000,
                'max_discount' => 1000,
                'usage_limit' => 100,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'ALYANS500',
                'description' => 'Фиксированная скидка 500₽',
                'type' => PromoCode::TYPE_FIXED,
                'value' => 500,
                'min_order_amount' => 3000,
                'max_discount' => null,
                'usage_limit' => 50,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(6),
            ],
            [
                'code' => 'SUMMER15',
                'description' => 'Летняя акция 15%',
                'type' => PromoCode::TYPE_PERCENT,
                'value' => 15,
                'min_order_amount' => 5000,
                'max_discount' => 2000,
                'usage_limit' => 200,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(2),
            ],
            [
                'code' => 'VIP20',
                'description' => 'VIP скидка 20% для постоянных клиентов',
                'type' => PromoCode::TYPE_PERCENT,
                'value' => 20,
                'min_order_amount' => 10000,
                'max_discount' => 5000,
                'usage_limit' => 30,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ],
            [
                'code' => 'EXPIRED2024',
                'description' => 'Истёкший промокод (тест)',
                'type' => PromoCode::TYPE_PERCENT,
                'value' => 50,
                'min_order_amount' => 0,
                'max_discount' => null,
                'usage_limit' => 10,
                'is_active' => false,
                'starts_at' => now()->subYear(),
                'expires_at' => now()->subMonth(),
            ],
        ];

        foreach ($promoCodes as $data) {
            PromoCode::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        // Дополнительно генерируем 5 случайных промокодов через Faker
        for ($i = 0; $i < 5; $i++) {
            PromoCode::create([
                'code' => strtoupper($faker->bothify('??##??')),
                'description' => $faker->sentence(4),
                'type' => $faker->randomElement([PromoCode::TYPE_PERCENT, PromoCode::TYPE_FIXED]),
                'value' => $faker->randomElement([5, 10, 15, 20, 100, 200, 300, 500]),
                'min_order_amount' => $faker->randomElement([0, 1000, 2000, 3000, 5000]),
                'max_discount' => $faker->boolean(50) ? $faker->randomElement([500, 1000, 2000, 3000]) : null,
                'usage_limit' => $faker->numberBetween(10, 500),
                'used_count' => $faker->numberBetween(0, 10),
                'is_active' => $faker->boolean(70),
                'starts_at' => now()->subDays($faker->numberBetween(0, 30)),
                'expires_at' => now()->addDays($faker->numberBetween(30, 180)),
            ]);
        }

        $this->command->info('Создано 10 промокодов.');
    }
}
