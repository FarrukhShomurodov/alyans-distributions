@php $template = $template ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Команда (без слэша)</label>
        <div class="flex items-center">
            <span class="px-3 py-2 bg-slate-200 dark:bg-navy-600 rounded-l-lg text-slate-700 font-mono">/</span>
            <input type="text" name="command" required maxlength="50"
                   value="{{ old('command', $template?->command) }}"
                   pattern="[a-zA-Zа-яА-Я0-9_-]+"
                   placeholder="оплата"
                   class="flex-1 rounded-r-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm font-mono focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
        </div>
        <div class="text-xs text-slate-500 mt-1">Латиница, кириллица, цифры, дефис, подчёркивание. Без пробелов.</div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Название</label>
        <input type="text" name="title" required maxlength="150"
               value="{{ old('title', $template?->title) }}"
               placeholder="Оплата по СБП"
               class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
        <div class="text-xs text-slate-500 mt-1">Видно менеджеру в выпадающем списке.</div>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1">Текст шаблона</label>
        <textarea name="text" required rows="6" maxlength="4000"
                  placeholder="Можете оплатить через ссылку: https://..."
                  class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ old('text', $template?->text) }}</textarea>
        <div class="text-xs text-slate-500 mt-1">Поддерживаются переносы строк. Максимум 4000 символов.</div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Порядок сортировки</label>
        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $template?->sort_order ?? 0) }}"
               class="w-full rounded-lg border border-slate-300 dark:border-navy-500 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
        <div class="text-xs text-slate-500 mt-1">Меньше = выше в списке.</div>
    </div>

    <div class="flex items-center pt-7">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="is_active"
               @checked(old('is_active', $template?->is_active ?? true))
               class="w-4 h-4 rounded border-slate-400 text-blue-600 focus:ring-blue-500">
        <label for="is_active" class="ml-2 text-sm text-slate-700">Активен (виден в чатах)</label>
    </div>
</div>

<div class="pt-4 flex justify-end gap-3">
    <a href="{{ route('chat-templates.index') }}"
       class="rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 px-5 py-2.5 text-sm font-medium transition">
        Отмена
    </a>
    <button type="submit"
            class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 text-sm font-medium transition">
        {{ $submitLabel ?? 'Сохранить' }}
    </button>
</div>
