@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Редактирование остатка</title>
@endsection

@section('content')
    <div class="py-8">
        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
                Редактирование остатка
            </h1>

            <a href="{{ route('stocks.index') }}"
               class="text-sm text-slate-500 hover:text-slate-700 dark:text-navy-200 transition">
                ← Назад к складу
            </a>
        </div>

        <!-- Форма -->
        <form action="{{ route('stocks.update', $stock->id) }}" method="post"
              class="bg-white dark:bg-navy-700 rounded-2xl shadow-md dark:shadow-[0_0_10px_rgba(0,0,0,0.2)] p-6 space-y-5 transition">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-semibold text-slate-800  mb-4">
                Информация об остатке
            </h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <!-- Продукт -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        Продукт
                    </label>
                    <div
                        class="w-full rounded-lg border border-slate-200 dark:border-navy-600  dark:bg-navy-800
                               px-3 py-2 text-slate-800  text-sm cursor-not-allowed">
                        {{ $stock->product?->name ?? 'Неизвестный продукт' }}
                    </div>
                </div>

                <!-- Количество -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        Количество
                    </label>
                    <input type="number" name="quantity" value="{{ old('quantity', $stock->quantity) }}" min="1"
                           required
                           class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800
                                  px-3 py-2 text-slate-800  text-sm focus:border-blue-500
                                  focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <!-- ID -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        ID записи
                    </label>
                    <div
                        class="w-full rounded-lg border border-slate-200 dark:border-navy-600  dark:bg-navy-800
                               px-3 py-2 text-slate-500 dark:text-slate-400 text-sm">
                        {{ $stock->id }}
                    </div>
                </div>

                <!-- Последнее обновление -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        Последнее обновление
                    </label>
                    <div
                        class="w-full rounded-lg border border-slate-200 dark:border-navy-600  dark:bg-navy-800
                               px-3 py-2 text-slate-500 dark:text-slate-500 text-sm">
                        {{ $stock->updated_at?->format('d.m.Y H:i') ?? '—' }}
                    </div>
                </div>

                <!-- Создан -->
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        Создан
                    </label>
                    <div
                        class="w-full rounded-lg border border-slate-200 dark:border-navy-600  dark:bg-navy-800
                               px-3 py-2 text-slate-500 dark:text-slate-500 text-sm">
                        {{ $stock->created_at?->format('d.m.Y H:i') ?? '—' }}
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
                    Сохранить изменения
                </button>
            </div>
        </form>

        <!-- Фильтры -->
        <div class="mt-8">
            <form method="GET" class="flex items-center gap-3 mb-4">
                <select name="source"
                        class="rounded-lg border border-slate-300 dark:border-navy-600 dark:bg-navy-800 p-2 text-sm">
                    <option value="">Все источники</option>
                    <option value="manual" @selected(request('source')==='manual')>Ручные изменения</option>
                    <option value="order" @selected(request('source')==='order')>Покупки (Order)</option>
                </select>

                <button
                    class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    Фильтр
                </button>
            </form>
        </div>

        <!-- История -->
        @php
            $historyItems = $stock->history()
                ->when(request('source'), fn($q) => $q->where('source', request('source')))
                ->orderByDesc('created_at')
                ->get();
        @endphp

        @if($historyItems->count() > 0)
            <div>
                <h2 class="text-lg font-semibold text-slate-800 mb-3">История изменений / покупок</h2>

                <div
                    class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
                        <thead class="dark:bg-navy-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">ID</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Тип</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Изменение</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Было</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Стало</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Источник</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Изменил</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Покупатель</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Дата</th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 dark:divide-navy-600 text-slate-700">
                        @foreach($historyItems as $history)
                            <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">

                                <!-- ID -->
                                <td class="px-4 py-3 text-sm font-medium">{{ $history->id }}</td>

                                <!-- Тип -->
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $history->type === 'plus'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200'
                                            : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200' }}">
                                        {{ $history->type === 'plus' ? 'Пополнение' : 'Списание' }}
                                    </span>
                                </td>

                                <!-- Разница -->
                                <td class="px-4 py-3 text-sm font-semibold">
                                    {{ $history->difference > 0 ? '+' . $history->difference : $history->difference }}
                                </td>

                                <!-- Было -->
                                <td class="px-4 py-3 text-sm">{{ $history->previous_quantity }}</td>

                                <!-- Стало -->
                                <td class="px-4 py-3 text-sm">{{ $history->previous_quantity + $history->difference }}</td>

                                <!-- Источник -->
                                <td class="px-4 py-3 text-sm">
                                    @if($history->source === 'manual')
                                        <span
                                            class="px-2 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200">
                                            Ручное изменение
                                        </span>
                                    @elseif($history->source === 'order')
                                        <div class="flex flex-col">
                                            <a href="{{ route('orders.show', $history->order_id) }}"
                                               class="px-2 py-1 rounded-full bg-amber-100 text-amber-200 dark:bg-amber-800 dark:text-amber-200text-xs font-medium hover:underline w-max">
                                                Покупка — Order #{{ $history->order_id }}
                                            </a>

                                            @if($history->order)
                                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                    Статус: {{ $history->order->status_name }}
                                                </div>

                                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                                    Сумма: {{ number_format($history->order->total, 0, '.', ' ') }} сум
                                                </div>

                                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                                    Позиции: {{ $history->order->items->count() }}
                                                </div>
                                            @endif
                                        </div>

                                    @elseif($history->source === 'telegram')
                                        <span
                                            class="px-2 py-1 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-800 dark:text-purple-200">
                                            Telegram
                                        </span>
                                    @else
                                        <span
                                            class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                            Система
                                        </span>
                                    @endif
                                </td>

                                <!-- Изменил -->
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                    @if($history->updatedBy)
                                        {{ $history->updatedBy->login }} (id:{{ $history->updatedBy->id }})
                                    @else
                                        —
                                    @endif
                                </td>

                                <!-- Покупатель -->
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                    @if($history->user)
                                        @if($history->user->uname)
                                            <a href="https://t.me/{{ $history->user->uname }}"
                                               target="_blank"
                                               class="text-blue-600 hover:underline">
                                                {{ $history->user->uname }}
                                            </a>
                                            <br>
                                            <a href="tel:{{ $history->user->phone }}"
                                               class="text-blue-600 hover:underline">
                                                {{ $history->user->phone }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                        <div class="text-xs text-slate-400">
                                            chat_id: {{ $history->user->chat_id }}
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>

                                <!-- Дата -->
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $history->created_at->format('d.m.Y H:i') }}
                                </td>

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        @else
            <div
                class="mt-6 p-4 rounded-xl border border-slate-200 dark:border-navy-600 bg-slate-50 dark:bg-navy-700 text-slate-500 dark:text-slate-500 text-center">
                История изменений отсутствует.
            </div>
        @endif
    </div>
@endsection
