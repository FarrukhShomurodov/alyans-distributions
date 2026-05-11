@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — {{ $product->name }}</title>
@endsection

@section('content')
    <div class="py-8">
        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
                Просмотр продукта
            </h1>

            <div class="flex items-center gap-3">
                <a href="{{ route('products.edit', $product->id) }}"
                   class="rounded-lg bg-blue-600 px-4 py-2 text-white text-sm font-medium hover:bg-blue-700 transition">
                    Редактировать
                </a>
                <a href="{{ route('products.index') }}"
                   class="text-sm border-slate-800 hover:text-slate-800 dark:text-navy-200 transition">
                    ← Назад к списку
                </a>
            </div>
        </div>

        <!-- Карточка продукта -->
        <div
            class="bg-white dark:bg-navy-700 rounded-2xl shadow-md dark:shadow-[0_0_10px_rgba(0,0,0,0.2)] p-6 space-y-6 transition">

            <!-- Основная информация -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-sm border-slate-800 dark:border-slate-800 font-medium mb-1">Название</h2>
                    <p class="text-lg font-semibold text-slate-800 ">{{ $product->name }}</p>
                </div>

                <div>
                    <h2 class="text-sm border-slate-800 dark:border-slate-800 font-medium mb-1">ID SOVA</h2>
                    <p class="text-lg font-semibold text-slate-800 ">{{ $product->external_id ?? '—' }}</p>
                </div>

                <div>
                    <h2 class="text-sm border-slate-800 dark:border-slate-800 font-medium mb-1">Категория</h2>
                    <p class="text-lg font-semibold text-slate-800 ">
                        {{ $product->category->name ?? '—' }}
                    </p>
                </div>

                <div>
                    <h2 class="text-sm border-slate-800 dark:border-slate-800 font-medium mb-1">Цена</h2>
                    <p class="text-lg font-semibold text-slate-800 ">
                        {{ number_format($product->price, 2) }} руб
                    </p>
                </div>

                <div>
                    <h2 class="text-sm border-slate-800 dark:border-slate-800 font-medium mb-1">Статус</h2>
                    @if($product->is_active)
                        <span
                            class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span> Активен
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium rounded-full bg-rose-100 text-rose-700 dark:bg-rose-800 dark:text-rose-200">
                            <span class="w-2 h-2 bg-rose-500 rounded-full"></span> Неактивен
                        </span>
                    @endif
                </div>
            </div>

            <!-- Галерея -->
            @if($product->images && $product->images->count() > 0)
                <div>
                    <h2 class="text-sm border-slate-800 dark:border-slate-800 font-medium mb-2">
                        Фотографии
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($product->images as $image)
                            <div
                                class="relative group rounded-xl overflow-hidden border border-slate-200 dark:border-navy-600 shadow-sm">
                                <img src="{{ '/storage/'.$image->url }}" alt="Фото продукта"
                                     class="w-full aspect-square object-cover transition-transform duration-300 group-hover:scale-105">
                                <a href="{{ '/storage/'.$image->url }}" target="_blank"
                                   class="absolute inset-0 bg-black/0 group-hover:bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                         stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="border-slate-800 dark:text-slate-400 text-sm italic">Фотографии отсутствуют</p>
            @endif
        </div>
    </div>
@endsection
