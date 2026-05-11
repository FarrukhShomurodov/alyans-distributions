<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Admin::query()->firstOrCreate(
            ['login' => 'test@gmail.com'],
            ['password' => Hash::make('test@gmail.com')]
        );

        $this->call([
            RolesAndPermissionsSeeder::class,
            CategoriesSeeder::class,
            ProductsSeeder::class,
            BotUsersSeeder::class,
            DiscountTiersSeeder::class,
            PromoCodesSeeder::class,
            OrdersSeeder::class,
            SliderProductsSeeder::class,
        ]);
    }
}
