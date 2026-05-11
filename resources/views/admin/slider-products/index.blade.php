@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Слайдер товаров</title>
@endsection

@section('content')
    <div class="py-8">
        {{-- Заголовок --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Слайдер товаров</h1>
                <p class="text-sm text-slate-500 mt-1">Добавляйте товары вручную — из них будет выбираться рандомная подборка</p>
            </div>
        </div>

        {{-- Уведомления --}}
        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Блок поиска и добавления --}}
        <div class="bg-white dark:bg-navy-700 rounded-2xl shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100 mb-4">
                <i class="fas fa-plus-circle text-indigo-500 mr-2"></i>Добавить товар в слайдер
            </h2>

            <form action="{{ route('slider-products.index') }}" method="GET" class="flex gap-3 mb-4">
                <input type="text" name="search" value="{{ $search ?? '' }}"
                       placeholder="Поиск по названию или ID товара..."
                       class="flex-1 rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 dark:border-navy-600 px-4 py-2.5 text-sm text-slate-800 dark:text-navy-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg transition">
                    <i class="fas fa-search mr-1"></i> Найти
                </button>
                @if($search)
                    <a href="{{ route('slider-products.index') }}"
                       class="px-4 py-2.5 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 transition flex items-center">
                        <i class="fas fa-times mr-1"></i> Сброс
                    </a>
                @endif
            </form>

            {{-- Результаты поиска --}}
            @if($search && $searchResults->isNotEmpty())
                <div class="border border-slate-200 dark:border-navy-600 rounded-xl overflow-hidden">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
                        <thead class="bg-slate-50 dark:bg-navy-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">ID</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Товар</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Категория</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Цена</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-slate-500">Действие</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
                            @foreach($searchResults as $product)
                                <tr class="hover:bg-slate-50 dark:hover:bg-navy-700">
                                    <td class="px-4 py-2 text-sm text-slate-500">{{ $product->id }}</td>
                                    <td class="px-4 py-2">
                                        <span class="text-sm font-medium text-slate-800 dark:text-navy-100">{{ $product->name }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-500">{{ $product->category?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-slate-700 dark:text-navy-100 font-medium">
                                        {{ number_format($product->price, 0, '', ' ') }} ₽
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <form action="{{ route('slider-products.store') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <button type="submit"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                                                <i class="fas fa-plus mr-1"></i> Добавить
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($search && $searchResults->isEmpty())
                <div class="text-center py-4 text-slate-400 text-sm">
                    <i class="fas fa-search text-2xl mb-2"></i>
                    <p>Товары не найдены или уже добавлены в слайдер</p>
                </div>
            @endif
        </div>

        {{-- Текущие товары в слайдере --}}
        <div class="bg-white dark:bg-navy-700 rounded-2xl shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-navy-600 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100">
                    <i class="fas fa-random text-amber-500 mr-2"></i>Товары в слайдере
                    <span class="text-sm font-normal text-slate-400 ml-2">({{ $sliderProducts->count() }} шт.)</span>
                </h2>
                <div class="text-xs text-slate-400">
                    <i class="fas fa-info-circle mr-1"></i> Из этих товаров рандомно выбираются для показа
                </div>
            </div>

            @if($sliderProducts->isNotEmpty())
                <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
                    <thead class="bg-slate-100 dark:bg-navy-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Порядок</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">ID</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Товар</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Категория</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-600 dark:text-navy-100">Цена</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Активен</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
                        @foreach($sliderProducts as $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 {{ !$item->is_active ? 'opacity-50' : '' }}">
                                {{-- Порядок (inline edit) --}}
                                <td class="px-4 py-3">
                                    <form action="{{ route('slider-products.update', $item) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="sort_order" value="{{ $item->sort_order }}"
                                               class="w-16 rounded border border-slate-300 bg-slate-50 dark:bg-navy-800 dark:border-navy-600 px-2 py-1 text-sm text-center"
                                               onchange="this.form.submit()">
                                        @if($item->is_active)
                                            <input type="hidden" name="is_active" value="1">
                                        @endif
                                    </form>
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-500">{{ $item->product->id }}</td>

                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-slate-800 dark:text-navy-100">
                                        {{ $item->product->name }}
                                    </span>
                                    @if($item->product->discount_percent > 0)
                                        <span class="ml-2 px-1.5 py-0.5 text-xs rounded bg-rose-100 text-rose-600 font-medium">
                                            -{{ $item->product->discount_percent }}%
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-500">
                                    {{ $item->product->category?->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-sm text-right font-medium text-slate-700 dark:text-navy-100">
                                    {{ number_format($item->product->price, 0, '', ' ') }} ₽
                                </td>

                                {{-- Активен toggle --}}
                                <td class="px-4 py-3 text-center">
                                    <form action="{{ route('slider-products.update', $item) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="sort_order" value="{{ $item->sort_order }}">
                                        @if(!$item->is_active)
                                            <input type="hidden" name="is_active" value="1">
                                        @endif
                                        <button type="submit" title="{{ $item->is_active ? 'Деактивировать' : 'Активировать' }}">
                                            @if($item->is_active)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                    <i class="fas fa-check mr-1"></i> Да
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                                    <i class="fas fa-times mr-1"></i> Нет
                                                </span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                {{-- Удалить --}}
                                <td class="px-4 py-3 text-center">
                                    <form action="{{ route('slider-products.destroy', $item) }}" method="POST"
                                          onsubmit="return confirm('Убрать товар из слайдера?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Удалить из слайдера"
                                                class="p-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-6 py-12 text-center text-slate-400">
                    <i class="fas fa-random text-4xl mb-3"></i>
                    <p class="text-lg">Слайдер пуст</p>
                    <p class="text-sm mt-1">Найдите товары через поиск выше и добавьте их</p>
                </div>
            @endif
        </div>
    </div>
@endsection
