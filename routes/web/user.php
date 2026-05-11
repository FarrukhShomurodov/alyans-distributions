<?php

// Telegram
use App\Http\Controllers\Telegram\LandingController;
use App\Http\Middleware\SetWebAppLocale;
use Illuminate\Support\Facades\Route;

Route::prefix('telegram/webapp')->middleware(SetWebAppLocale::class)->group(function () {

    Route::get('/', [LandingController::class, 'categories'])->name('webapp');
    Route::get('/category/{category}', [LandingController::class, 'categoryProducts'])
        ->name('webapp.category.products');
    Route::get('/products', [LandingController::class, 'allProducts'])
        ->name('webapp.products');
    Route::get('/product/show/{product}', [LandingController::class, 'product'])->name('webapp.product.show');
    Route::get('/cart', [LandingController::class, 'cart'])->name('webapp.cart');
    Route::get('/checkout', [LandingController::class, 'checkout'])->name('webapp.checkout');
    Route::get('/profile', [LandingController::class, 'profile'])->name('webapp.profile');
    Route::get('/favorites', [LandingController::class, 'favorites'])->name('webapp.favorites');

});
