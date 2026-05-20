@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Редактирование продукта</title>
@endsection

@section('content')
    <div class="py-8">
        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
                Редактирование продукта
            </h1>

            <a href="{{ route('products.index', request()->query()) }}"
               class="text-sm text-slate-500 hover:text-slate-800 dark:text-navy-200 transition">
                ← Назад к списку
            </a>
        </div>

        <!-- Форма -->
        <form action="{{ route('products.update', $product->id) }}" method="post" enctype="multipart/form-data"
              class="bg-white dark:bg-navy-700 rounded-2xl shadow-md dark:shadow-[0_0_10px_rgba(0,0,0,0.2)] p-6 space-y-5 transition">
            @csrf
            @method('PUT')
            {{-- Пропускаем фильтры через форму --}}
            @foreach(request()->query() as $_k => $_v)
                @if(is_array($_v))
                    @foreach($_v as $_kk => $_vv)
                        <input type="hidden" name="_back[{{ $_k }}][{{ $_kk }}]" value="{{ $_vv }}">
                    @endforeach
                @else
                    <input type="hidden" name="_back[{{ $_k }}]" value="{{ $_v }}">
                @endif
            @endforeach

            <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">
                Информация о продукте
            </h2>

            <!-- Основные поля -->
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-slate-800 mb-1">
                        Название
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                           class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 dark:text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-slate-800 mb-1">
                        Артикул (External ID)
                    </label>
                    <input type="text" name="external_id" value="{{ old('external_id', $product->external_id) }}"
                           class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 dark:text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                           placeholder="Необязательно">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-slate-800 mb-1">
                        Категория
                    </label>
                    <select name="category_id" required
                            class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 dark:text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected($product->category_id == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-slate-800 mb-1">
                        Цена

                    </label>
                    <input type="number" name="price" value="{{ old('price', $product->price) }}" min="0" step="0.01"
                           required
                           class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 dark:text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-slate-800 mb-1">
                        Скидка (%)
                    </label>
                    <input type="number" name="discount_percent" value="{{ old('discount_percent', $product->discount_percent) }}" min="0" max="100" step="1"
                           class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 dark:text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                           placeholder="0">
                </div>

                <div class="flex items-center space-x-2 pt-6">
                    <input type="hidden" name="is_active" value="0">

                    <input type="checkbox" name="is_active" value="1" @checked($product->is_active)
                    class="w-4 h-4 rounded border-slate-400 text-blue-600 focus:ring-blue-500
                  dark:bg-navy-800 dark:border-navy-500 dark:checked:bg-blue-600">
                    <label class="text-sm text-slate-800">Активен</label>
                </div>
            </div>

            @if($attributes->isNotEmpty())
                @php
                    $selectedAttributes = $product->attributes
                        ->groupBy('id')
                        ->map(fn($items) => $items->pluck('pivot.value')->all());
                @endphp
                <div>
                    <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Атрибуты</h2>
                    <div class="grid sm:grid-cols-2 gap-5">
                        @foreach($attributes as $attribute)
                            <div>
                                <label class="block text-sm font-medium text-slate-800 dark:text-slate-800 mb-2">
                                    {{ $attribute->name }}
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($attribute->values as $value)
                                        @php
                                            $oldValues = collect(old('attributes.'.$attribute->id, $selectedAttributes[$attribute->id] ?? []));
                                        @endphp
                                        <label class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-800">
                                            <input type="checkbox"
                                                   name="attributes[{{ $attribute->id }}][]"
                                                   value="{{ $value->value }}"
                                                   @checked($oldValues->contains($value->value))
                                                   class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            <span>{{ $value->value }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif


            <!-- Уже загруженные фото -->
            @if($product->images && $product->images->count() > 0)
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-slate-800 mb-2">
                        Уже загруженные фото
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3">
                        @foreach($product->images as $image)
                            <div class="relative group" data-image-id="{{ $image->id }}">
                                <img src="{{ '/storage/'.$image->url }}" alt="Фото продукта"
                                     class="w-full aspect-square object-cover rounded-lg border border-slate-200 dark:border-navy-500 shadow-sm">
                                <button type="button"
                                        class="absolute top-1 right-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center shadow-md transition"
                                        onclick="deleteProductImage({{ $image->id }}, this)"
                                        title="Удалить">✕
                                </button>
                            </div>
                        @endforeach
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
                    <input type="file" id="photos" name="photos[]" multiple accept="image/*" class="hidden">
                </div>

                <div id="preview-container" class="mt-4 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3"></div>
            </div>

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
            let selectedFiles = [];

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
                addFiles(e.dataTransfer.files);
            });

            input.addEventListener('change', e => addFiles(e.target.files));

            function addFiles(fileList) {
                Array.from(fileList).forEach(file => {
                    if (!file.type.startsWith('image/')) return;
                    if (selectedFiles.length >= 10) return;
                    selectedFiles.push(file);
                });
                syncInputAndPreview();
            }

            function syncInputAndPreview() {
                // Кладём выбранные файлы в input, чтобы они ушли на сервер
                const dt = new DataTransfer();
                selectedFiles.forEach(f => dt.items.add(f));
                input.files = dt.files;
                handleFiles(selectedFiles);
            }

            // === Удаление существующего фото продукта по ID ===
            window.deleteProductImage = function (imageId, btn) {
                if (!confirm('Удалить это изображение?')) return;

                btn.disabled = true;
                btn.textContent = '…';

                fetch(`/api/images/${imageId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                    .then(r => r.json().catch(() => ({success: r.ok})))
                    .then(data => {
                        if (data.success) {
                            const wrapper = btn.closest('[data-image-id]');
                            if (wrapper) {
                                wrapper.style.transition = 'opacity 0.2s, transform 0.2s';
                                wrapper.style.opacity = '0';
                                wrapper.style.transform = 'scale(0.9)';
                                setTimeout(() => wrapper.remove(), 250);
                            }
                        } else {
                            alert(data.message || 'Не удалось удалить изображение');
                            btn.disabled = false;
                            btn.textContent = '✕';
                        }
                    })
                    .catch(() => {
                        alert('Ошибка при соединении с сервером.');
                        btn.disabled = false;
                        btn.textContent = '✕';
                    });
            };

            // === Отрисовка превью выбранных фото ===
            function handleFiles(files) {
                previewContainer.innerHTML = '';
                const arr = Array.from(files);

                arr.forEach((file, index) => {
                    if (!file.type.startsWith('image/')) return;

                    const reader = new FileReader();
                    reader.onload = e => {
                        const div = document.createElement('div');
                        div.className = 'relative group transition-all duration-300';
                        div.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}"
                         class="w-full aspect-square object-cover rounded-lg border border-slate-200 dark:border-navy-500 shadow-sm">
                    <button type="button"
                        class="absolute top-1 right-1 bg-red-600 text-white text-xs rounded-full px-1.5 py-0.5 opacity-0 group-hover:opacity-100 transition"
                        title="Удалить">✕</button>
                `;
                        previewContainer.appendChild(div);

                        div.querySelector('button').addEventListener('click', () => {
                            selectedFiles.splice(index, 1);
                            syncInputAndPreview();
                        });
                    };
                    reader.readAsDataURL(file);
                });
            }

        });
    </script>
@endsection
