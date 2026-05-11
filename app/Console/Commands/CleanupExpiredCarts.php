<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

class CleanupExpiredCarts extends Command
{
    protected $signature = 'cart:cleanup {--minutes=30 : Cart expiration time in minutes}';

    protected $description = 'Delete carts that have been inactive for more than N minutes (default: 30)';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        $count = Cart::where('updated_at', '<', $cutoff)
            ->whereHas('items')
            ->count();

        if ($count === 0) {
            $this->info('No expired carts found.');
            return self::SUCCESS;
        }

        // Delete cart items first, then carts
        $expiredCarts = Cart::where('updated_at', '<', $cutoff)
            ->whereHas('items')
            ->get();

        foreach ($expiredCarts as $cart) {
            $cart->items()->delete();
            $cart->update(['promo_code_id' => null]);
        }

        $this->info("Cleaned up {$count} expired carts (inactive > {$minutes} min).");

        return self::SUCCESS;
    }
}
