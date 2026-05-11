<?php

use App\Http\Controllers\Dashboard\Api\ImageController;
use App\Http\Controllers\Integration\OneCController;
use App\Http\Controllers\Integration\SovaController;
use App\Http\Middleware\AuthenticateOneC;
use App\Http\Controllers\Telegram\Api\CartController;
use App\Http\Controllers\Telegram\Api\FavoriteController;
use App\Http\Controllers\Telegram\Api\OrderController;
use App\Http\Controllers\Telegram\Api\PaycomController;
use App\Http\Controllers\Telegram\Api\ProductShareController;
use App\Http\Controllers\Telegram\Api\ReviewController;
use App\Http\Controllers\Telegram\Api\UserController;
use Illuminate\Support\Facades\Route;

// delete image
Route::delete('delete/image/{folderName}/{fileName}', [ImageController::class, 'deletePhoto']);
Route::delete('images/{id}', [ImageController::class, 'destroy']);

Route::prefix('webapp')
    ->group(function () {

        // user
        Route::get('check-user', [UserController::class, 'checkActive']);
        Route::get('user/info', [UserController::class, 'info']);

        // favorites
        Route::get('favorite/list', [FavoriteController::class, 'list']);
        Route::post('favorite/toggle', [FavoriteController::class, 'toggle']);
        Route::get('favorite/check', [FavoriteController::class, 'check']);

        // cart
        Route::get('cart/data', [CartController::class, 'data']);
        Route::post('cart/add', [CartController::class, 'add']);
        Route::post('cart/update', [CartController::class, 'update']);
        Route::post('cart/remove', [CartController::class, 'remove']);
        Route::get('cart/count', [CartController::class, 'count']);
        Route::get('cart/items', [CartController::class, 'getProductQty']);
        Route::post('cart/apply-promo', [CartController::class, 'applyPromo']);
        Route::post('cart/remove-promo', [CartController::class, 'removePromo']);

        // order
        Route::post('order/create', [OrderController::class, 'create']);

        // delivery
        Route::get('delivery/cdek-pvz', [\App\Http\Controllers\Telegram\Api\DeliveryController::class, 'cdekPvz']);
        Route::post('delivery/cdek-calculate', [\App\Http\Controllers\Telegram\Api\DeliveryController::class, 'cdekCalculate']);
        Route::get('delivery/yandex-pvz', [\App\Http\Controllers\Telegram\Api\DeliveryController::class, 'yandexPvz']);

        // geocoding (server-side via Yandex HTTP Geocoder API)
        Route::get('geo/reverse', [\App\Http\Controllers\Telegram\Api\DeliveryController::class, 'reverseGeocode']);
        Route::get('geo/forward', [\App\Http\Controllers\Telegram\Api\DeliveryController::class, 'forwardGeocode']);

        // product share
        Route::post('product/share', [ProductShareController::class, 'share']);

        // reviews
        Route::get('reviews/list', [ReviewController::class, 'list']);
        Route::post('reviews/add', [ReviewController::class, 'add']);

    });

// paycom
Route::post('paycom', [PaycomController::class, 'handleRequest']);

Route::get('integrations/1c/health', [OneCController::class, 'health']);

Route::prefix('integrations/1c')
    ->middleware(AuthenticateOneC::class)
    ->group(function () {
        Route::get('orders', [OneCController::class, 'orders']);
        Route::post('orders/mark-exported', [OneCController::class, 'markOrdersExported']);
        Route::patch('orders/{order}/status', [OneCController::class, 'updateOrderStatus']);
        Route::post('stocks/sync', [OneCController::class, 'syncStocks']);
    });

Route::get('integrations/sova/health', [SovaController::class, 'health']);

Route::prefix('integrations/sova')
    ->middleware(AuthenticateOneC::class)
    ->group(function () {
        Route::post('sync', [SovaController::class, 'syncAll']);
        Route::post('sync/categories', [SovaController::class, 'syncCategories']);
        Route::post('sync/products', [SovaController::class, 'syncProducts']);
        Route::post('sync/stocks', [SovaController::class, 'syncStocks']);
        Route::post('export/orders', [SovaController::class, 'exportOrders']);
    });
