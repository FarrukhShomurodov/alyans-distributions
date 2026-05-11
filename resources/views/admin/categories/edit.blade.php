@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Редактирование категории</title>
@endsection

@section('content')
    <div class="py-8">
        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
                Редактирование категории
            </h1>

            <a href="{{ route('categories.index') }}"
               class="text-sm text-slate-500 hover:text-slate-700 dark:text-navy-200 transition">
                ← Назад к списку
            </a>
        </div>

        <!-- Форма -->
        <form action="{{ route('categories.update', $category->id) }}" method="post"
              enctype="multipart/form-data"
              class="bg-white dark:bg-navy-700 rounded-2xl shadow-md dark:shadow-[0_0_10px_rgba(0,0,0,0.2)] p-6 space-y-5 transition">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-semibold text-slate-800  mb-4">
                Информация о категории
            </h2>

            <!-- Основные поля -->
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        Название
                    </label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                           class="w-full rounded-lg border border-slate-300 dark:border-navy-500
                                  bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm
                                  focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700  mb-1">
                        Скидка (%)
                    </label>
                    <input type="number" name="discount_percent" min="0" max="100" value="{{ old('discount_percent', $category->discount_percent) }}"
                           class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <div class="flex items-center space-x-2 pt-6">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($category->is_active)
                    class="w-4 h-4 rounded border-slate-400 text-blue-600 focus:ring-blue-500
                                  dark:bg-navy-800 dark:border-navy-500 dark:checked:bg-blue-600">
                    <label class="text-sm text-slate-700 ">Активна</label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700  mb-1">
                    Родительская категория
                </label>
                <select name="parent_id"
                        class="w-full rounded-lg border border-slate-300 dark:border-navy-500
                               bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm
                               focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                    <option value="">— Нет —</option>
                    @include('admin.categories.partials.category-options', [
                        'categories' => $categoriesTree ?? collect(),
                        'selectedParentId' => old('parent_id', $category->parent_id),
                        'excludedCategoryIds' => $excludedCategoryIds ?? collect()
                    ])
                </select>
            </div>

            <!-- Описание -->
            <div>
                <label class="block text-sm font-medium text-slate-700  mb-1">
                    Описание
                </label>
                <textarea name="description" rows="3" required
                          class="w-full rounded-lg border border-slate-300 dark:border-navy-500
                                 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800  text-sm resize-none
                                 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">{{ old('description', $category->description) }}</textarea>
            </div>

            @if($category->photo_url)
                <div>
                    <label class="block text-sm font-medium mb-2">
                        Текущее фото
                    </label>

                    <div class="relative w-40">
                        <img src="{{ asset('storage/'.$category->photo_url) }}"
                             class="w-full rounded-lg border shadow">
                    </div>
                </div>
            @endif


            <!-- Загрузка новых фото -->
            <div>
                <label class="block text-sm font-medium text-slate-800 dark:text-slate-800 mb-2">
                    Добавить новые фото (до 10)
                </label>
                <div id="drop-zone"
                     class="border-2 border-dashed border-slate-300 dark:border-navy-500 rounded-xl p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-slate-50/50 dark:hover:bg-navy-600/50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor"
                         class="mx-auto w-10 h-10 text-slate-400 dark:text-slate-300 mb-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-9 0v-9m0 0L7.5 10.5M12 7.5l4.5 3"/>
                    </svg>
                    <p class="text-sm text-slate-500 dark:text-slate-300">
                        Перетащите файлы или кликните для выбора
                    </p>
                    <input type="file" id="photos" name="photo_url" accept="image/*" class="hidden">
                </div>

                <div id="preview-container" class="mt-4 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3"></div>
            </div>

            <!-- Кнопка -->
            <div class="pt-4 flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
                    Сохранить изменения
                </button>
            </div>
        </form>
    </div>
@endsection


@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('photos');
            const dropZone = document.getElementById('drop-zone');
            const previewContainer = document.getElementById('preview-container');

            // === Drag & Drop ===
            dropZone.addEventListener('click', () => input.click());

            dropZone.addEventListener('dragover', e => {
                e.preventDefault();
                dropZone.classList.add('border-blue-400', 'bg-blue-50/30');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-blue-400', 'bg-blue-50/30');
            });

            dropZone.addEventListener('drop', e => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-400', 'bg-blue-50/30');
                handleFiles(e.dataTransfer.files);
            });

            input.addEventListener('change', e => handleFiles(e.target.files));

            // === Отрисовка превью выбранных фото ===
            function handleFiles(files) {
                previewContainer.innerHTML = '';

                const file = files[0];
                if (!file || !file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement('div');
                    div.className = 'relative group';
                    div.innerHTML = `
            <img src="${e.target.result}"
                 class="w-full aspect-square object-cover rounded-lg border shadow-sm">
            <button type="button"
                class="absolute top-1 right-1 bg-red-600 text-white text-xs rounded-full px-2 py-0.5"
                title="Удалить">✕</button>
        `;

                    previewContainer.appendChild(div);

                    div.querySelector('button').onclick = () => {
                        div.remove();
                        input.value = '';
                    };
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
@endsection
