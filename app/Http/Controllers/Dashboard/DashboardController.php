<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController
{
    public function index(Request $request): View
    {
        // ===== ОБЩАЯ СТАТИСТИКА =====
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', Order::STATUS_DONE)->sum('total');
        $totalDesserts = OrderItem::sum('quantity');

        // ===== ДОПОЛНИТЕЛЬНАЯ СТАТИСТИКА =====
        $totalUsers = \App\Models\BotUser::count();
        $activeUsers = \App\Models\BotUser::where('is_active', 1)->count();
        $inactiveUsers = \App\Models\BotUser::where('is_active', 0)->count();
        $newUsersMonth = \App\Models\BotUser::where('created_at', '>=', now()->subMonth())->count();
        $newUsersYear = \App\Models\BotUser::where('created_at', '>=', now()->subYear())->count();

        $totalProducts = \App\Models\Product::count();
        $activeProducts = \App\Models\Product::where('is_active', 1)->count();
        $inactiveProducts = \App\Models\Product::where('is_active', 0)->count();
        $avgProductPrice = \App\Models\Product::avg('price');
        $maxDiscountProduct = \App\Models\Product::orderByDesc('discount_percent')->first();

        $totalCategories = \App\Models\Category::count();
        $activeCategories = \App\Models\Category::where('is_active', 1)->count();
        $inactiveCategories = \App\Models\Category::where('is_active', 0)->count();
        $categoriesWithProducts = \App\Models\Category::withCount('products')->get();

        $totalReviews = \App\Models\Review::count();
        $avgReviewRating = \App\Models\Review::avg('rating');
        $reviewRatingsDistribution = \App\Models\Review::select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('total', 'rating');

        $avgOrderTotal = Order::where('status', Order::STATUS_DONE)->avg('total');
        $avgOrderItems = 0;
        $doneOrders = Order::where('status', Order::STATUS_DONE)->withCount('items')->get();
        if ($doneOrders->count() > 0) {
            $avgOrderItems = $doneOrders->avg('items_count');
        }

        // Детализация заказов по типу оплаты
        $ordersByPaymentType = Order::select('payment_type', DB::raw('count(*) as total'))
            ->groupBy('payment_type')
            ->pluck('total', 'payment_type');
        // Детализация заказов по типу доставки
        $ordersByDeliveryType = Order::select('delivery_type', DB::raw('count(*) as total'))
            ->groupBy('delivery_type')
            ->pluck('total', 'delivery_type');

        // Статистика по заказам за месяц/год
        $ordersMonth = Order::where('created_at', '>=', now()->subMonth())->count();
        $ordersYear = Order::where('created_at', '>=', now()->subYear())->count();

        // Статистика по корзинам
        $avgCartSize = \App\Models\Cart::withCount('items')->get()->avg('items_count');
        $emptyCarts = \App\Models\Cart::doesntHave('items')->count();

        // Статистика по поддержке
        $supportChats = \App\Models\SupportChat::count();
        $supportMessages = \App\Models\SupportMessage::count();

        // Статистика по транзакциям
        $totalTransactions = \App\Models\Transaction::count();
        $successfulTransactions = \App\Models\Transaction::where('state', 1)->count();
        $failedTransactions = \App\Models\Transaction::where('state', 0)->count();
        $transactionsSum = \App\Models\Transaction::sum('amount');

        // Популярные товары (по количеству заказов)
        $topProducts = \App\Models\OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->with('product')
            ->limit(5)
            ->get();

        // ===== ЗАКАЗЫ ПО СТАТУСАМ =====
        $ordersByStatus = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // ===== ЗАКАЗЫ ПО ДНЯМ (ЗА 14 ДНЕЙ) =====
        $ordersByDays = Order::select(
            DB::raw('DATE(created_at) as day'),
            DB::raw('count(*) as total')
        )
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('admin.dashboard', compact(
            'totalOrders',
            'totalRevenue',
            'totalDesserts',
            'ordersByStatus',
            'ordersByDays',
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'totalProducts',
            'activeProducts',
            'inactiveProducts',
            'totalCategories',
            'activeCategories',
            'inactiveCategories',
            'totalReviews',
            'avgOrderTotal',
            'avgOrderItems',
            'topProducts',
            'ordersByDeliveryType',
        ));
    }
}
