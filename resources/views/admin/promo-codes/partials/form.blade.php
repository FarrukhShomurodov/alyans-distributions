@php($p = $promoCode ?? null)

<h2 class="text-lg font-semibold text-slate-800 mb-4">Информация о промокоде</h2>

<div class="grid sm:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Код промокода</label>
        <input type="text" name="code" value="{{ old('code', $p?->code) }}" required
               placeholder="SALE2026"
               class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm font-mono uppercase focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
        @error('code')
            <span class="text-xs text-red-500 mt-1">{{ $message }}</span>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Описание</label>
        <input type="text" name="description" value="{{ old('description', $p?->description) }}"
               placeholder="Весенняя распродажа"
               class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
    </div>
</div>

<div class="grid sm:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Тип скидки</label>
        <select name="type"
                class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
            <option value="percent" @selected(old('type', $p?->type) === 'percent')>Процент (%)</option>
            <option value="fixed" @selected(old('type', $p?->type) === 'fixed')>Фиксированная сумма (руб.)</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Значение скидки</label>
        <input type="number" name="value" value="{{ old('value', $p?->value) }}" required min="1"
               placeholder="10"
               class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
    </div>
</div>

<div class="grid sm:grid-cols-3 gap-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Мин. сумма заказа (руб.)</label>
        <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $p?->min_order_amount) }}" min="0"
               placeholder="0"
               class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Макс. скидка (руб.)</label>
        <input type="number" name="max_discount" value="{{ old('max_discount', $p?->max_discount) }}" min="1"
               placeholder="Без ограничения"
               class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Лимит использований</label>
        <input type="number" name="usage_limit" value="{{ old('usage_limit', $p?->usage_limit) }}" min="1"
               placeholder="Без ограничения"
               class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
    </div>
</div>

<div class="grid sm:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Дата начала</label>
        <input type="datetime-local" name="starts_at"
               value="{{ old('starts_at', $p?->starts_at?->format('Y-m-d\TH:i')) }}"
               class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Дата окончания</label>
        <input type="datetime-local" name="expires_at"
               value="{{ old('expires_at', $p?->expires_at?->format('Y-m-d\TH:i')) }}"
               class="w-full rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition">
    </div>
</div>

<div class="flex items-center space-x-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p?->is_active ?? true))
           class="w-4 h-4 rounded border-slate-400 text-purple-600 focus:ring-purple-500">
    <label class="text-sm text-slate-700">Активен</label>
</div>

@if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <ul class="text-sm text-red-600 list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
