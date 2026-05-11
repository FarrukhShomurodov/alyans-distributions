@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Продукты</title>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Продукты</h1>

        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="document.getElementById('import-modal').classList.toggle('hidden')"
                    class="rounded-full bg-amber-600 px-5 py-2.5 text-white font-medium hover:bg-amber-700 transition text-sm">
                <i class="fas fa-file-import mr-1"></i> Импорт из Excel
            </button>

            <button onclick="window.location.href='{{ route('products.export') }}'"
                    class="rounded-full bg-emerald-600 px-5 py-2.5 text-white font-medium hover:bg-emerald-700 transition text-sm">
                <i class="fas fa-file-export mr-1"></i> Экспорт в Excel
            </button>

            <button onclick="window.location.href='{{ route('products.create', request()->query()) }}'"
                    class="rounded-full bg-blue-600 px-5 py-2.5 text-white font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition text-sm">
                Добавить продукт
            </button>
        </div>
    </div>

    <div class="mb-6 bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
        <form method="GET" action="{{ route('products.index') }}" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700  mb-1">Поиск</label>
                <input type="text" name="search" value="{{ $search ?? '' }}"
                       placeholder="ID, название, slug"
                       class="w-full rounded-lg border border-slate-300  bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700  mb-1">Категория</label>
                <select name="category_id"
                        class="w-full rounded-lg border border-slate-300  bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">Все категории</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Фото</label>
                <select name="photo"
                        class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">Все</option>
                    <option value="with" @selected(($photoFilter ?? '') === 'with')>📷 С фото</option>
                    <option value="without" @selected(($photoFilter ?? '') === 'without')>🚫 Без фото</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                    Фильтровать
                </button>
                <a href="{{ route('products.index') }}"
                   class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-300 transition">
                    Сбросить
                </a>
            </div>
        </form>
    </div>

    <!-- 📊 Статистика -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
            <div class="text-sm text-slate-500 ">Всего продуктов</div>
            <div class="text-2xl font-semibold text-slate-800  mt-1">
                {{ $stats['total_products'] ?? $products->total() }}
            </div>
        </div>

        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
            <div class="text-sm text-slate-500 ">Всего категорий</div>
            <div class="text-2xl font-semibold text-slate-800  mt-1">
                {{ $stats['total_categories'] ?? $categoriesCount ?? '-' }}
            </div>
        </div>

        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
            <div class="text-sm text-slate-500 ">Средняя цена</div>
            <div class="text-2xl font-semibold text-slate-800  mt-1">
                {{ number_format($stats['avg_price'] ?? $products->avg('price'), 0) }} руб
            </div>
        </div>

        <div
            class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-700 p-4">
            <div class="text-sm text-emerald-600 ">Активные</div>
            <div class="text-2xl font-semibold text-emerald-700  mt-1">
                {{ $stats['active'] ?? $products->where('is_active', true)->count() }}
            </div>
        </div>

        <div
            class="bg-rose-50 dark:bg-rose-900/20 rounded-xl shadow-sm border dark:border-rose-400 p-4">
            <div class="text-sm text-rose-600 dark:text-rose-400">Неактивные</div>
            <div class="text-2xl font-semibold text-rose-700  mt-1">
                {{ $stats['inactive'] ?? $products->where('is_active', false)->count() }}
            </div>
        </div>
    </div>

    <!-- Таблица -->
    <div
        class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">ID SOVA</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Название</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Категория</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Цена</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Фото</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Статус</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Действия</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-navy-600 text-slate-700 dark:text-navy-100">
            @foreach($products as $product)
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                    <td class="px-4 py-3 text-sm font-medium">{{ $product->id }}</td>
                    <td class="px-4 py-3 text-sm font-medium">{{ $product->external_id }}</td>

                    <td class="px-4 py-3">
                        <a href="{{ route('products.show', $product->id) }}"
                           class="text-blue-600 hover:text-blue-800 font-semibold transition group relative inline-block">
                            {{ $product->name }}
                            <span
                                class="absolute left-0 -bottom-0.5 h-0.5 w-0 bg-blue-600 transition-all duration-300 group-hover:w-full"></span>
                        </a>
                    </td>


                    <td class="px-4 py-3 text-sm">{{ $product->category->name ?? '—' }}</td>

                    <td class="px-4 py-3 text-sm font-semibold">{{ number_format($product->price, 2) }} </td>

                    <!-- Фото -->
                    <td class="px-4 py-3 text-center">
                        @if(($product->images_count ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200"
                                  title="Есть {{ $product->images_count }} фото">
                                📷 {{ $product->images_count }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-rose-100 text-rose-700 dark:bg-rose-800 dark:text-rose-200"
                                  title="Нет фото">
                                🚫 Нет
                            </span>
                        @endif
                    </td>

                    <!-- Статус -->
                    <td class="px-4 py-3 text-center">
                        @if($product->is_active)
                            <span
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200">
                                <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                Активен
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-rose-100 text-rose-700 dark:bg-rose-800 dark:text-rose-200">
                                <span class="w-2 h-2 bg-rose-500 rounded-full"></span>
                                Неактивен
                            </span>
                        @endif
                    </td>

                    <!-- Действия -->
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center space-x-2">
                            <button title="Редактировать"
                                    onclick="window.location.href='{{ route('products.edit', array_merge(['product' => $product->id], request()->query())) }}'"
                                    class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>

                            <button title="Просмотреть"
                                    onclick="window.location.href='{{ route('products.show', $product->id) }}'"
                                    class="p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>

                            <form action="{{ route('products.destroy', $product->id) }}" method="POST"
                                  onsubmit="return confirm('Удалить продукт?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Удалить"
                                        class="p-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <x-pagination :paginator="$products"/>
    </div>

    {{-- Модалка импорта --}}
    <div id="import-modal" class="hidden fixed inset-0 z-100 flex items-center justify-center bg-black/50" style="z-index: 100">
        <div class="bg-white dark:bg-navy-800 rounded-2xl shadow-xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-navy-50">Импорт товаров из Excel</h3>
                <button onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-500">
                <p class="font-medium mb-1">Формат файла (заголовки столбцов):</p>
                <p>ID | External ID | Название | Описание | Цена | Категория | Статус | Скидка</p>
                <p class="mt-1 text-xs">Если ID или External ID (ID SOVA) товара найден — товар обновляется. Иначе создается новый.</p>
            </div>

            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-200 mb-1">Файл Excel (.xlsx, .xls, .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                           class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('import-modal').classList.add('hidden')"
                            class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 transition">
                        Отмена
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-medium transition">
                        Импортировать
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
