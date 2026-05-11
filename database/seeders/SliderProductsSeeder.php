<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SliderProduct;
use Illuminate\Database\Seeder;

class SliderProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::where('is_active', true)
            ->inRandomOrder()
            ->limit(15)
            ->get();

        foreach ($products as $i => $product) {
            SliderProduct::firstOrCreate(
                ['product_id' => $product->id],
                ['sort_order' => $i + 1, 'is_active' => true]
            );
        }

        $this->command->info('Добавлено ' . $products->count() . ' товаров в слайдер.');
    }
}
