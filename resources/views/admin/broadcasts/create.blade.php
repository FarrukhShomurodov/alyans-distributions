@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Создание рассылки</title>
@endsection

@section('content')
    <div class="py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Создание рассылки</h1>
            <a href="{{ route('broadcasts.index') }}" class="text-sm text-slate-500 hover:text-slate-700 transition">
                ← Назад к списку
            </a>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-6 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                <i class="fas fa-users"></i>
                <span class="text-sm font-medium">Активных пользователей: {{ $usersCount }}</span>
            </div>
            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Рассылка будет отправлена всем активным пользователям бота</p>
        </div>

        <form action="{{ route('broadcasts.store') }}" method="POST" enctype="multipart/form-data"
              class="bg-white dark:bg-navy-700 rounded-2xl shadow-md p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Название рассылки</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       placeholder="Название для внутреннего использования"
                       class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                @error('title')
                    <span class="text-xs text-red-500 mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Текст сообщения (поддерживает HTML)</label>
                <textarea name="message" rows="6" required
                          placeholder="Текст рассылки. Поддерживает HTML-теги: <b>, <i>, <a>, <code>"
                          class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm resize-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition font-mono">{{ old('message') }}</textarea>
                @error('message')
                    <span class="text-xs text-red-500 mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Фото (необязательно)</label>
                <input type="file" name="photo" accept="image/*"
                       class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-sm">
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('broadcasts.index') }}"
                   class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 transition">
                    Отмена
                </a>
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
                    Сохранить как черновик
                </button>
            </div>
        </form>
    </div>
@endsection
