@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Карусель</title>
@endsection

@section('content')
    <div class="py-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Карусель на главной</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="card p-5">
                    <h2 class="text-lg font-medium text-slate-700 dark:text-navy-100 mb-4">Добавить слайд</h2>

                    <form action="{{ route('landing.carousel.store') }}" method="post" enctype="multipart/form-data"
                          class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium mb-1">Фото</label>
                            <input type="file" name="image" accept="image/*" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Подпись</label>
                            <input type="text" name="title" placeholder="Например: Новинки"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Категория (ссылка)</label>
                            <select name="category_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 select2">
                                <option value="">Без ссылки</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Порядок</label>
                            <input type="number" name="sort_order" min="0" value="0"
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>

                        <button type="submit"
                                class="px-4 py-2 bg-black text-white rounded-lg">
                            Сохранить
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="card p-5">
                    <h2 class="text-lg font-medium text-slate-700 dark:text-navy-100 mb-4">Слайды</h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-2 pr-4">Фото</th>
                                <th class="py-2 pr-4">Подпись</th>
                                <th class="py-2 pr-4">Категория</th>
                                <th class="py-2 pr-4">Порядок</th>
                                <th class="py-2 pr-4">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($carouselItems as $item)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-4">
                                        <img src="/storage/{{ $item->image_path }}" alt="slide"
                                             class="h-16 w-32 object-cover">
                                    </td>
                                    <td class="py-2 pr-4">
                                        <form action="{{ route('landing.carousel.update', $item->id) }}" method="post"
                                              class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="title" value="{{ $item->title }}"
                                                   class="w-44 rounded-md border border-slate-300 px-2 py-1">
                                    </td>
                                    <td class="py-2 pr-4">
                                            <select name="category_id"
                                                    class="rounded-md border border-slate-300 px-2 py-1">
                                                <option value="">Без ссылки</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                        @selected($item->category_id === $category->id)>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                    </td>
                                    <td class="py-2 pr-4">
                                            <input type="number" name="sort_order" min="0"
                                                   value="{{ $item->sort_order }}"
                                                   class="w-24 rounded-md border border-slate-300 px-2 py-1">
                                    </td>
                                    <td class="py-2 pr-4">
                                            <button type="submit" class="text-blue-600 hover:underline">Сохранить</button>
                                        </form>

                                        <form action="{{ route('landing.carousel.destroy', $item->id) }}" method="post"
                                              onsubmit="return confirm('Удалить слайд?')"
                                              class="mt-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-slate-500">Слайды отсутствуют</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
