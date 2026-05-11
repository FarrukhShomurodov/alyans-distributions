<?php

namespace Database\Seeders;

use App\Models\BotUser;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class BotUsersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('ru_RU');

        for ($i = 0; $i < 30; $i++) {
            BotUser::create([
                'chat_id' => $faker->unique()->numberBetween(100000, 9999999),
                'first_name' => $faker->firstName(),
                'second_name' => $faker->lastName(),
                'uname' => $faker->unique()->userName(),
                'phone' => $faker->phoneNumber(),
                'step' => $faker->randomElement(['start', 'menu', 'catalog', 'cart', 'order']),
                'is_active' => $faker->boolean(85),
                'lang' => $faker->randomElement(['ru', 'uz', null]),
            ]);
        }

        $this->command->info('Создано 30 тестовых пользователей бота.');
    }
}
