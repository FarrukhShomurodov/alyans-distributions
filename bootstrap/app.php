<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Sync stock from file every 5 minutes (СОВА / universal)
        $schedule->command('stock:sync-file --source=sova --delete-after')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/stock-sync.log'));

        // Sync categories, products, stocks from Sova API every 30 minutes
        $schedule->command('sova:sync')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/sova-sync.log'));

        // Export new orders to Sova every 30 minutes
        $schedule->command('sova:export-orders')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/sova-export-orders.log'));

        // Clean expired carts (30 min timeout)
        $schedule->command('cart:cleanup')
            ->everyFiveMinutes()
            ->withoutOverlapping();
    })
    ->withRouting(
        web: [
            __DIR__ . '/../routes/web/admin.php',
            __DIR__ . '/../routes/web/user.php',
        ],
        api: __DIR__ . '/../routes/api/routes.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }

            $guard = $request->route()->middleware();
            if ($guard[1] == 'auth:admin') {
                return route('dashboard.login');
            } else {
                return route('auth.view');
            }
        });

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        $middleware->web([
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();
