<?php

namespace Database\Seeders;

use App\Models\DiscountTier;
use Illuminate\Database\Seeder;

class DiscountTiersSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['min_amount' => 5000, 'discount_percent' => 5],
            ['min_amount' => 10000, 'discount_percent' => 7],
            ['min_amount' => 15000, 'discount_percent' => 10],
            ['min_amount' => 50000, 'discount_percent' => 15],
        ];

        foreach ($tiers as $tier) {
            DiscountTier::updateOrCreate(
                ['min_amount' => $tier['min_amount']],
                $tier
            );
        }
    }
}
