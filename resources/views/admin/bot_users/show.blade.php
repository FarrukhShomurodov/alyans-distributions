@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Пользователь #{{ $user->id }}</title>
@endsection

@section('content')

    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
            Пользователь: {{ $user->first_name }} {{ $user->second_name }}
        </h1>

        <a href="{{ route('bot.users.index') }}"
           class="rounded-full bg-slate-600 px-6 py-2.5 text-white font-medium hover:bg-slate-700 transition">
            ← Назад
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        <!-- Информация о пользователе -->
        <div class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4 text-slate-800 dark:text-navy-50">Профиль</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">ID:</span>
                    <span class="font-medium">{{ $user->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Имя:</span>
                    <span class="font-medium">{{ $user->first_name ?? '—' }} {{ $user->second_name ?? '' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Телефон:</span>
                    <span class="font-medium">{{ $user->phone ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Telegram:</span>
                    <span class="font-medium">
                        @if($user->uname)
                            <a href="https://t.me/{{ $user->uname }}" target="_blank" class="text-blue-600 hover:underline">{{ '@' . $user->uname }}</a>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Chat ID:</span>
                    <span class="font-medium text-xs text-slate-400">{{ $user->chat_id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Язык:</span>
                    <span class="font-medium">{{ $user->lang ?? 'ru' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Статус:</span>
                    <span class="px-2 py-1 rounded text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $user->is_active ? 'Активен' : 'Заблокирован' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Регистрация:</span>
                    <span class="font-medium">{{ $user->created_at?->format('d.m.Y H:i') ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Статистика заказов -->
        <div class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4 text-slate-800 dark:text-navy-50">Заказы</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Всего заказов:</span>
                    <span class="font-semibold">{{ $user->orders->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Сумма заказов:</span>
                    <span class="font-semibold">{{ number_format($user->orders->sum('total'), 0, '', ' ') }} сум</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Завершённых:</span>
                    <span class="font-medium text-emerald-600">{{ $user->orders->where('status', 'done')->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Отменённых:</span>
                    <span class="font-medium text-rose-600">{{ $user->orders->where('status', 'canceled')->count() }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Список заказов -->
    @if($user->orders->isNotEmpty())
        <div class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm">
            <div class="p-6 border-b border-slate-200 dark:border-navy-600">
                <h2 class="text-lg font-semibold text-slate-800 dark:text-navy-50">
                    История заказов ({{ $user->orders->count() }})
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
                    <thead class="bg-slate-100 dark:bg-navy-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold">ID</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Дата</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Сумма</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Статус</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Оплата</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
                    @foreach($user->orders->sortByDesc('id') as $order)
                        <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('orders.show', $order) }}" class="text-blue-600 hover:underline font-medium">
                                    #{{ $order->id }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium">{{ number_format($order->total, 0, '', ' ') }} сум</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded text-xs font-medium {{ match($order->status) {
                                    'new' => 'bg-blue-100 text-blue-700',
                                    'confirmed' => 'bg-cyan-100 text-cyan-700',
                                    'in_process' => 'bg-amber-100 text-amber-700',
                                    'delivery' => 'bg-violet-100 text-violet-700',
                                    'done' => 'bg-emerald-100 text-emerald-700',
                                    'canceled' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-slate-100 text-slate-700',
                                } }}">
                                    {{ $order->status_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $order->payment_status_name }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
