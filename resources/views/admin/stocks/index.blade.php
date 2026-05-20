@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Склад</title>

    <style>
        .qty-danger {
            background: #fee2e2; /* красный фон */
            color: #b91c1c; /* красный текст */
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
        }

        .qty-warning {
            background: #fef3c7; /* оранжевый фон */
            color: #b45309; /* оранжевый текст */
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
        }

        .qty-normal {
            font-weight: 600;
            color: #0f172a;
        }
    </style>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Склад</h1>
        <div class="flex gap-2">
            <a href="{{ route('stocks.export') }}"
               class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium transition">
                Экспорт
            </a>
            <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')"
                    class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition">
                Импорт остатков
            </button>
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="importModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-navy-50">Импорт остатков</h3>
                <button onclick="document.getElementById('importModal').classList.add('hidden')"
                        class="text-slate-400 hover:text-slate-600 text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('stocks.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">Файл (CSV, XLSX, XLS)</label>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls" required
                           class="w-full rounded-lg border-slate-300 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-50 text-sm">
                </div>
                <input type="hidden" name="source" value="file">
                <div class="mb-4 p-3 rounded-lg bg-slate-50 dark:bg-navy-700 text-xs text-slate-600 dark:text-navy-200">
                    <p class="font-semibold mb-1">Формат файла:</p>
                    <p>Колонки: <strong>код товара</strong> (или external_id, id) + <strong>количество</strong> (или остаток, qty)</p>
                    <p class="mt-1">Если товар найден по коду — остаток обновляется, иначе пропускается.</p>
                    <a href="{{ route('stocks.template') }}" class="text-blue-600 hover:underline mt-1 inline-block">Скачать шаблон</a>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                            class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-medium">
                        Отмен��
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
                        Загрузить
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-6 p-4 rounded-xl border border-slate-200 dark:border-navy-600 bg-white dark:bg-navy-800">
        <form method="GET" action="{{ route('stocks.index') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="w-full sm:w-72">
                <label for="search" class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">
                    Поиск
                </label>
                <input id="search" name="search" type="text" value="{{ $search ?? '' }}"
                       placeholder="ID склада, ID продукта, название"
                       class="w-full rounded-lg border-slate-300 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-50 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="w-full sm:w-72">
                <label for="stock_filter" class="block text-sm font-medium text-slate-700 dark:text-navy-100 mb-1">
                    Фильтр по остаткам
                </label>
                <select id="stock_filter" name="stock_filter"
                        class="w-full rounded-lg border-slate-300 dark:border-navy-500 dark:bg-navy-700 dark:text-navy-50 focus:border-blue-500 focus:ring-blue-500">
                    <option value="" @selected(($stockFilter ?? '') === '')>Все остатки</option>
                    <option value="out" @selected(($stockFilter ?? '') === 'out')>0 — товара нет</option>
                    <option value="low" @selected(($stockFilter ?? '') === 'low')>1–5 — низкий остаток</option>
                    <option value="restock" @selected(($stockFilter ?? '') === 'restock')>≤5 — требуется пополнение</option>
                    <option value="ok" @selected(($stockFilter ?? '') === 'ok')>>5 — достаточный остаток</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition">
                    Фильтровать
                </button>

                <a href="{{ route('stocks.index') }}"
                   class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-medium transition">
                    Сбросить
                </a>
            </div>
        </form>
    </div>

    <!-- Статистика -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
            <div class="text-sm text-slate-500 ">Всего записей</div>
            <div class="text-2xl font-semibold text-slate-800  mt-1">
                {{ $stocks->total() }}
            </div>
        </div>

        <div
            class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-700 p-4">
            <div class="text-sm text-emerald-600 dark:text-emerald-500">Общее количество товаров</div>
            <div class="text-2xl font-semibold text-emerald-700 dark:text-emerald-500 mt-1">
                {{ $stocks->sum('quantity') }}
            </div>
        </div>

        <div
            class="bg-blue-50 dark:bg-blue-900/20 rounded-xl shadow-sm border border-blue-200 dark:border-blue-700 p-4">
            <div class="text-sm text-blue-600 dark:text-blue-400">Уникальных продуктов</div>
            <div class="text-2xl font-semibold text-blue-700 dark:text-blue-400 mt-1">
                {{ $stocks->unique('product_id')->count() }}
            </div>
        </div>
    </div>

    <div class="mb-6 p-4 rounded-xl border border-slate-200 dark:border-navy-600 bg-white dark:bg-navy-800">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-navy-50 mb-3">
            Статус остатков
        </h2>

        <div class="space-y-2 text-sm text-slate-600 dark:text-navy-200">

            <div class="flex items-center space-x-3">
                <span class="inline-block w-4 h-4 rounded bg-red-200 border border-red-400"></span>
                <span class="font-medium text-red-700 dark:text-red-400">
                0 — товара нет на складе (критично)
            </span>
            </div>

            <div class="flex items-center space-x-3">
                <span class="inline-block w-4 h-4 rounded bg-amber-200 border border-amber-400"></span>
                <span class="font-medium text-amber-700 dark:text-amber-400">
                1–5 — низкий остаток, требуется пополнение
            </span>
            </div>

            <div class="flex items-center space-x-3">
                <span class="inline-block w-4 h-4 rounded bg-slate-200 border border-slate-400"></span>
                <span class="font-medium text-slate-700 dark:text-slate-300">
                >5 — остаток достаточный
            </span>
            </div>

        </div>
    </div>

    <!-- Таблица -->
    <div
        class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 ">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 ">Продукт</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 ">Количество</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 ">Обновлено</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 ">Действия</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-navy-600 text-slate-700 ">
            @forelse($stocks as $stock)
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                    <td class="px-4 py-3 text-sm font-medium">{{ $stock->id }}</td>

                    <td class="px-4 py-3">
                        @if($stock->product)
                            <a href="{{ route('products.show', $stock->product->id) }}"
                               class="text-blue-600 hover:text-blue-800 font-semibold transition group relative inline-block">
                                {{ $stock->product->name }}
                                <span
                                    class="absolute left-0 -bottom-0.5 h-0.5 w-0 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                            </a>
                        @else
                            <span class="text-slate-400 italic">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-sm">

                        @if($stock->quantity == 0)
                            <span class="qty-danger">0</span>

                        @elseif($stock->quantity <= 5)
                            <span class="qty-warning">{{ $stock->quantity }}</span>

                        @else
                            <span class="qty-normal">{{ $stock->quantity }}</span>
                        @endif

                    </td>


                    <td class="px-4 py-3 text-sm text-slate-500 ">
                        {{ $stock->updated_at?->format('d.m.Y H:i') ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center space-x-2">

                            <button title="Просмотреть"
                                    onclick="window.location.href='{{ route('stocks.show', $stock->id) }}'"
                                    class="p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>

                            <!-- Редактировать -->
                            <button title="Редактировать"
                                    onclick="window.location.href='{{ route('stocks.edit', $stock->id) }}'"
                                    class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-slate-500 ">
                        Нет записей на складе.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <x-pagination :paginator="$stocks"/>
    </div>
@endsection
