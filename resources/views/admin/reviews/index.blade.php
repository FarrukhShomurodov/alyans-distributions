@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Отзывы</title>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
            Отзывы
        </h1>
    </div>

    <!-- 🔍 ФИЛЬТРЫ -->
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Product -->
        <div>
            <label class="text-sm text-slate-500">Товар</label>
            <select name="product_id"
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-800">
                <option value="">Все</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}"
                        {{ request('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- User -->
        <div>
            <label class="text-sm text-slate-500">Пользователь</label>
            <select name="user_id"
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-800">
                <option value="">Все</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}"
                        {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->first_name }} {{ $user->second_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Rating -->
        <div>
            <label class="text-sm text-slate-500">Рейтинг</label>
            <select name="rating"
                    class="mt-1 w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-800">
                <option value="">Все</option>
                @for($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>
                        {{ $i }} ★
                    </option>
                @endfor
            </select>
        </div>

        <!-- Text search -->
        <div>
            <label class="text-sm text-slate-500">Поиск по тексту</label>
            <input type="text" name="text" value="{{ request('text') }}"
                   placeholder="Введите слово..."
                   class="mt-1 w-full rounded-lg border-slate-300 dark:border-navy-600 dark:bg-navy-800"/>
        </div>

        <div class="md:col-span-4 flex justify-end space-x-3 mt-2">
            <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                Фильтровать
            </button>

            <a href="{{ route('reviews.index') }}"
               class="px-4 py-2 bg-slate-200 rounded-lg dark:bg-navy-700 text-slate-700 dark:text-navy-50">
                Сбросить
            </a>
        </div>
    </form>

    <!-- 📄 Таблица отзывов -->
    <div
        class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold">ID</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Товар</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Пользователь</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Рейтинг</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Текст</th>
                <th class="px-4 py-3 text-center text-sm font-semibold">Дата</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
            @foreach($reviews as $review)
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                    <td class="px-4 py-3 text-sm font-medium">{{ $review->id }}</td>

                    <td class="px-4 py-3 text-sm">
                        {{ $review->product->name ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-sm">
                        {{ $review->user->first_name ?? '—' }} {{ $review->user->second_name ?? '' }}
                    </td>

                    <td class="px-4 py-3 font-semibold text-amber-600">
                        {{ $review->rating }} ★
                    </td>

                    <td class="px-4 py-3 text-sm">
                        {{ Str::limit($review->text, 80) }}
                    </td>

                    <td class="px-4 py-3 text-sm text-center text-slate-500">
                        {{ $review->created_at->format('d.m.Y H:i') }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="mt-4">
        {{ $reviews->links() }}
    </div>

@endsection
