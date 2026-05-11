@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS – CRM Analytics</title>
@endsection

@section('content')


    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Всего заказов -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Всего заказов</div>
            <div class="text-3xl font-bold mt-1">{{ $totalOrders }}</div>
        </div>
        <!-- Общая выручка -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Выручка</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($totalRevenue, 0, '.', ' ') }} руб</div>
        </div>
        <!-- Средний чек -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Средний чек</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($avgOrderTotal, 0, '.', ' ') }} руб</div>
        </div>
        <!-- Среднее кол-во товаров в заказе -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Товаров в заказе (ср.)</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($avgOrderItems, 2) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Пользователи -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Пользователей</div>
            <div class="text-2xl font-bold mt-1">{{ $totalUsers }}</div>
            <div class="text-xs text-emerald-600">Активных: {{ $activeUsers }}</div>
            <div class="text-xs text-rose-600">Неактивных: {{ $inactiveUsers }}</div>
        </div>
        <!-- Продукты -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Продуктов</div>
            <div class="text-2xl font-bold mt-1">{{ $totalProducts }}</div>
            <div class="text-xs text-emerald-600">Активных: {{ $activeProducts }}</div>
            <div class="text-xs text-rose-600">Неактивных: {{ $inactiveProducts }}</div>
        </div>
        <!-- Категории -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Категорий</div>
            <div class="text-2xl font-bold mt-1">{{ $totalCategories }}</div>
            <div class="text-xs text-emerald-600">Активных: {{ $activeCategories }}</div>
            <div class="text-xs text-rose-600">Неактивных: {{ $inactiveCategories }}</div>
        </div>
        {{-- Отзывы скрыты --}}
        {{-- <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Отзывы</div>
            <div class="text-2xl font-bold mt-1">{{ $totalReviews ?? 0 }}</div>
        </div> --}}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Продано десертов -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Продано товаров</div>
            <div class="text-3xl font-bold mt-1">{{ $totalDesserts }}</div>
        </div>
        <!-- Топ-5 популярных товаров -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Топ-5 популярных товаров</div>
            <ol class="mt-2 text-sm">
                @foreach($topProducts as $item)
                    <li>
                        {{ $item->product->name ?? '—' }} — <span class="font-bold">{{ $item->total_sold }}</span> шт.
                    </li>
                @endforeach
            </ol>
        </div>
        <!-- Тип доставки -->
        <div class="bg-white p-5 rounded-xl shadow">
            <div class="text-gray-500 text-sm">Тип доставки</div>
            <ul class="mt-2 text-sm">
                @foreach($ordersByDeliveryType as $type => $count)
                    @php
                        $typeLabel = [
                            'delivery' => 'Доставка',
                            'pickup' => 'Самовывоз',
                        ][$type] ?? $type;
                    @endphp
                    <li>
                        <span class="font-bold">{{ $typeLabel }}</span>: {{ $count }} заказов
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- ГРАФИКИ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        <!-- Заказы по дням -->
        <div class="bg-white p-5 rounded-xl shadow">
            <h3 class="font-semibold mb-3">Заказы по дням</h3>
            <canvas id="ordersByDaysChart"></canvas>
        </div>

        <!-- Заказы по статусам -->
        <div class="bg-white p-5 rounded-xl shadow">
            <h3 class="font-semibold mb-3">Статусы заказов</h3>
            <canvas id="ordersByStatusChart"></canvas>
        </div>

    </div>
@endsection


@section('scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // ===== ГРАФИК ПО ДНЯМ =====
        const daysLabels = @json($ordersByDays->pluck('day'));
        const daysData = @json($ordersByDays->pluck('total'));

        new Chart(document.getElementById('ordersByDaysChart'), {
            type: 'line',
            data: {
                labels: daysLabels,
                datasets: [{
                    label: 'Заказы',
                    data: daysData,
                    borderWidth: 2,
                    tension: 0.4
                }]
            }
        });

        // ===== ГРАФИК ПО СТАТУСАМ =====
        const statusLabelsRaw = @json($ordersByStatus->keys());
        const statusData = @json($ordersByStatus->values());

        // Человеко-понятные статусы
        const statusLabels = statusLabelsRaw.map(key => ({
            'new': 'Новый',
            'done': 'Завершён',
            'in_process': 'В процессе',
            'canceled': 'Отменён',
        }[key] || key));

        new Chart(document.getElementById('ordersByStatusChart'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData
                }]
            }
        });
    </script>

@endsection
