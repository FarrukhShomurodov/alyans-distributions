@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Редактирование атрибута</title>
@endsection

@section('content')
    <div class="py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50 mb-6">Редактировать атрибут</h1>

        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-6">
            <form action="{{ route('attributes.update', $attribute) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-slate-700  mb-1">Название</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $attribute->name) }}" required
                           class="w-full rounded-lg border border-slate-300  bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium text-slate-700  mb-1">Тип</label>
                    <select name="type" id="type" required
                            class="w-full rounded-lg border border-slate-300  bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                        <option value="string" {{ old('type', $attribute->type) == 'string' ? 'selected' : '' }}>Строка</option>
                        <option value="number" {{ old('type', $attribute->type) == 'number' ? 'selected' : '' }}>Число</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block text-sm font-medium text-slate-700  mb-1">Категория</label>
                    <select name="category_id" id="category_id" required
                            class="w-full rounded-lg border border-slate-300  bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                        <option value="">Выберите категорию</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $attribute->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <a href="{{ route('attributes.index') }}" class="mr-4 rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">Отмена</a>
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Обновить</button>
                </div>
            </form>
        </div>

        <div class="mt-6 bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-navy-50 mb-4">Значения атрибута</h2>

            <form action="{{ route('attributes.values.store', $attribute) }}" method="POST" class="flex flex-col sm:flex-row gap-3 mb-4">
                @csrf
                <input
                    type="{{ $attribute->type === 'number' ? 'number' : 'text' }}"
                    name="value"
                    value="{{ old('value') }}"
                    required
                    class="flex-1 rounded-md border-slate-300 dark:border-navy-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Введите значение"
                >
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Добавить</button>
            </form>
            @error('value')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap gap-2">
                @forelse($attribute->values as $value)
                    <div class="flex items-center gap-2 rounded-full bg-slate-100 dark:bg-navy-700 px-3 py-1">
                        <span class="text-sm text-slate-800 dark:text-navy-50">{{ $value->value }}</span>
                        <form action="{{ route('attributes.values.destroy', [$attribute, $value]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">×</button>
                        </form>
                    </div>
                @empty
                    <span class="text-sm text-slate-500">Нет значений</span>
                @endforelse
            </div>
        </div>
    </div>
@endsection