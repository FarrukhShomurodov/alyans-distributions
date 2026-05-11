<?php

use App\Http\Controllers\Dashboard\AdminAuthController;
use App\Http\Controllers\Dashboard\AdminController;
use App\Http\Controllers\Dashboard\AttributeController;
use App\Http\Controllers\Dashboard\BroadcastController;
use App\Http\Controllers\Dashboard\BotUserController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\ChatTemplateController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\ProductController;
use App\Http\Controllers\Dashboard\PromoCodeController;
use App\Http\Controllers\Dashboard\PromotionController;
use App\Http\Controllers\Dashboard\ReviewController;
use App\Http\Controllers\Dashboard\SliderProductController;
use App\Http\Controllers\Dashboard\StockController;
use App\Http\Controllers\Dashboard\SupportChatController;
use App\Http\Controllers\Dashboard\SupportMessageController;
use App\Http\Controllers\Telegram\TelegramController;
use App\Http\Controllers\Telegram\LandingController;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\Api;

Route::get('admin/login', [AdminAuthController::class, 'showLoginForm'])->name('dashboard.login');
Route::post('admin/login', [AdminAuthController::class, 'login']);

Route::post('dashboard/logout', [AdminAuthController::class, 'logout'])->name('logout')->middleware('auth:admin');

Route::prefix('dashboard')->middleware(['auth:admin'])->group(function () {

    Route::get('', [DashboardController::class, 'index'])->name('dashboard');

    // Support (доступно всем)
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SupportChatController::class, 'index'])->name('index');
        Route::get('/unread-count', [SupportChatController::class, 'unreadCount'])->name('unread-count');
        Route::get('/{chat}', [SupportChatController::class, 'show'])->name('show');
        Route::get('/{chat}/poll', [SupportChatController::class, 'poll'])->name('poll');
        Route::post('/{chat}/send', [SupportMessageController::class, 'send'])->name('send');
        Route::post('/{chat}/close', [SupportMessageController::class, 'close'])->name('close');
    });

    // Chat templates — шаблоны ответов для чатов (доступно всем менеджерам)
    Route::get('/chat-templates/api', [ChatTemplateController::class, 'api'])->name('chat-templates.api');
    Route::resource('chat-templates', ChatTemplateController::class)->except(['show']);

    // =========================
    // Admin Has Full Access + Commodity Expert & Admin Routes
    // =========================
    Route::middleware('role:admin|commodity_expert')->group(function () {
        // categories
        Route::resource('categories', CategoryController::class);

        // attributes
        Route::resource('attributes', AttributeController::class);
        Route::post('attributes/{attribute}/values', [AttributeController::class, 'storeValue'])
            ->name('attributes.values.store');
        Route::delete('attributes/{attribute}/values/{value}', [AttributeController::class, 'destroyValue'])
            ->name('attributes.values.destroy');

        // products
        Route::resource('products', ProductController::class);
        Route::get('/export-products', [ProductController::class, 'export'])
            ->name('products.export');
        Route::post('/import-products', [ProductController::class, 'import'])
            ->name('products.import');
        Route::get('/import-products/template', [ProductController::class, 'importTemplate'])
            ->name('products.import.template');

        // stocks
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/edit/{stock}', [StockController::class, 'edit'])->name('stocks.edit');
        Route::put('stocks/edit/{stock}', [StockController::class, 'update'])->name('stocks.update');
        Route::get('stocks/show/{stock}', [StockController::class, 'show'])->name('stocks.show');
        Route::post('stocks/import', [StockController::class, 'import'])->name('stocks.import');
        Route::get('stocks/export', [StockController::class, 'export'])->name('stocks.export');
        Route::get('stocks/template', [StockController::class, 'template'])->name('stocks.template');
    });

    // =========================
    // Admin Has Full Access + Manager & Admin Routes
    // =========================
    Route::middleware('role:admin|manager')->group(function () {
        // orders
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/export-orders', [OrderController::class, 'export'])
            ->name('orders.export');
        Route::post(
            '/orders/{order}/status',
            [\App\Http\Controllers\Dashboard\OrderController::class, 'updateStatus']
        )
            ->name('orders.update-status');

        Route::post(
            '/orders/{order}/message',
            [\App\Http\Controllers\Dashboard\OrderController::class, 'sendMessage']
        )
            ->name('orders.send-message');

        Route::get(
            '/orders/{order}/chat/poll',
            [\App\Http\Controllers\Dashboard\OrderController::class, 'pollChat']
        )
            ->name('orders.chat-poll');

        Route::post(
            '/orders/{order}/chat/close',
            [\App\Http\Controllers\Dashboard\OrderController::class, 'closeChat']
        )
            ->name('orders.close-chat');

        // bot-users
        Route::get('bot-users', [BotUserController::class, 'index'])->name('bot.users.index');
        Route::get('bot-users/{user}', [BotUserController::class, 'show'])->name('bot.users.show');

        // reviews — скрыто
        // Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');

        // broadcasts
        Route::get('broadcasts', [BroadcastController::class, 'index'])->name('broadcasts.index');
        Route::get('broadcasts/create', [BroadcastController::class, 'create'])->name('broadcasts.create');
        Route::post('broadcasts', [BroadcastController::class, 'store'])->name('broadcasts.store');
        Route::post('broadcasts/{broadcast}/send', [BroadcastController::class, 'send'])->name('broadcasts.send');
        Route::delete('broadcasts/{broadcast}', [BroadcastController::class, 'destroy'])->name('broadcasts.destroy');
    });

    // =========================
    // Admin Only Routes
    // =========================
    Route::middleware('role:admin')->group(function () {
        // admins
        Route::resource('admins', AdminController::class)->except('show');

        // landing carousel
        Route::get('landing-carousel', [LandingController::class, 'carouselIndex'])
            ->name('landing.carousel.index');
        Route::post('landing-carousel', [LandingController::class, 'carouselStore'])
            ->name('landing.carousel.store');
        Route::put('landing-carousel/{carousel}', [LandingController::class, 'carouselUpdate'])
            ->name('landing.carousel.update');
        Route::delete('landing-carousel/{carousel}', [LandingController::class, 'carouselDestroy'])
            ->name('landing.carousel.destroy');

        // promotions
        Route::get('promotions', [PromotionController::class, 'index'])
            ->name('promotions.index');
        Route::post('promotions', [PromotionController::class, 'update'])
            ->name('promotions.update');

        // discount tiers
        Route::post('discount-tiers', [PromotionController::class, 'storeTier'])
            ->name('discount-tiers.store');
        Route::put('discount-tiers/{tier}', [PromotionController::class, 'updateTier'])
            ->name('discount-tiers.update');
        Route::delete('discount-tiers/{tier}', [PromotionController::class, 'destroyTier'])
            ->name('discount-tiers.destroy');

        // promo codes
        Route::resource('promo-codes', PromoCodeController::class)->except('show');

        // slider products (рандомный слайдер)
        Route::get('slider-products', [SliderProductController::class, 'index'])->name('slider-products.index');
        Route::post('slider-products', [SliderProductController::class, 'store'])->name('slider-products.store');
        Route::put('slider-products/{sliderProduct}', [SliderProductController::class, 'update'])->name('slider-products.update');
        Route::delete('slider-products/{sliderProduct}', [SliderProductController::class, 'destroy'])->name('slider-products.destroy');
        Route::get('slider-products/search', [SliderProductController::class, 'search'])->name('slider-products.search');

        // disactive bot
        Route::post('admin/bot-users/{user}/toggle-block', [BotUserController::class, 'toggleBlock'])
            ->name('admin.bot-users.toggle-block');
    });
});

// Telegram
Route::prefix('telegram')->group(function () {
    Route::get('webhook', function () {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $hook = $telegram->setWebhook(['url' => env('TELEGRAM_WEBHOOK_URL')]);
        dd($hook);
    });

    Route::post('webhook', [TelegramController::class, 'handleWebhook']);
});
