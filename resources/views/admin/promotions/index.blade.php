@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Акции и скидки</title>
@endsection

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Акции и скидки</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-navy-200">
            Управление глобальными акциями и пороговыми скидками по сумме заказа
        </p>
    </div>

    {{-- Раздел 1: Глобальная акция --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100 mb-4">
            <i class="fas fa-tag mr-2 text-blue-500"></i>Глобальная акция
        </h2>
        <p class="text-sm text-slate-500 dark:text-navy-200 mb-4">
            Выберите только одну активную акцию. Одновременно две акции работать не могут.
        </p>

        <form action="{{ route('promotions.update') }}" method="post" class="space-y-4">
            @csrf

            <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-6">
                <label class="flex items-start gap-3">
                    <input type="radio" name="active_type"
                           value="{{ \App\Models\PromotionSetting::TYPE_PERCENT }}"
                           @checked(old('active_type', $promotion->active_type) === \App\Models\PromotionSetting::TYPE_PERCENT)
                           class="mt-1"/>
                    <div class="flex-1">
                        <div class="text-base font-medium text-slate-800 dark:text-navy-50">
                            Скидка в N % на все товары
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="number" min="1" max="100" name="discount_percent" id="discount_percent"
                                   value="{{ old('discount_percent', $promotion->discount_percent) }}"
                                   class="w-24 rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-slate-800 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                   placeholder="0"/>
                            <span class="text-sm text-slate-500">%</span>
                        </div>
                    </div>
                </label>
            </div>

            <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-6">
                <label class="flex items-start gap-3">
                    <input type="radio" name="active_type"
                           value="{{ \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO }}"
                           @checked(old('active_type', $promotion->active_type) === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO)
                           class="mt-1"/>
                    <div class="flex-1">
                        <div class="text-base font-medium text-slate-800 dark:text-navy-50">
                            1+2 (две позиции дешевле первой — бесплатно)
                        </div>
                        <p class="mt-1 text-sm text-slate-500 dark:text-navy-200">
                            Покупатель оплачивает самый дорогой товар, два более дешёвых — по цене 0.
                        </p>
                    </div>
                </label>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
                    Сохранить акцию
                </button>
            </div>
        </form>
    </div>

    {{-- Раздел 2: Пороговые скидки по сумме заказа --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100 mb-4">
            <i class="fas fa-percent mr-2 text-emerald-500"></i>Скидки по сумме заказа (п.6.4)
        </h2>
        <p class="text-sm text-slate-500 dark:text-navy-200 mb-4">
            Автоматическая скидка применяется к заказу, если сумма превышает указанный порог.
            Применяется наибольший подходящий порог.
        </p>

        {{-- Существующие пороги --}}
        <div class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 overflow-hidden mb-4">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
                <thead class="bg-slate-50 dark:bg-navy-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Сумма от</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Скидка (%)</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Статус</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
                    @forelse($discountTiers as $tier)
                        <tr class="hover:bg-slate-50 dark:hover:bg-navy-700">
                            <td class="px-4 py-3">
                                <form action="{{ route('discount-tiers.update', $tier) }}" method="POST" class="flex items-center gap-2" id="tier-form-{{ $tier->id }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="min_amount" value="{{ $tier->min_amount }}" min="1"
                                           class="w-32 rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"/>
                                    <span class="text-sm text-slate-500">руб.</span>
                            </td>
                            <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="discount_percent" value="{{ $tier->discount_percent }}" min="1" max="100"
                                               class="w-20 rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"/>
                                        <span class="text-sm text-slate-500">%</span>
                                    </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                    <label class="inline-flex items-center">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($tier->is_active)
                                               class="w-4 h-4 rounded border-slate-400 text-blue-600 focus:ring-blue-500">
                                    </label>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <button type="submit" title="Сохранить"
                                            class="p-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>

                                    <form action="{{ route('discount-tiers.destroy', $tier) }}" method="POST"
                                          onsubmit="return confirm('Удалить порог?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Удалить"
                                                class="p-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-400">
                                Пороги скидок не заданы. Добавьте первый порог ниже.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Добавить новый порог --}}
        <form action="{{ route('discount-tiers.store') }}" method="POST"
              class="bg-white dark:bg-navy-800 rounded-xl shadow-sm border border-slate-200 dark:border-navy-600 p-4">
            @csrf
            <div class="flex items-end gap-4 flex-wrap">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-200 mb-1">Сумма от (руб.)</label>
                    <input type="number" name="min_amount" min="1" required placeholder="5000"
                           class="w-40 rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"/>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-navy-200 mb-1">Скидка (%)</label>
                    <input type="number" name="discount_percent" min="1" max="100" required placeholder="5"
                           class="w-24 rounded-lg border border-slate-300 bg-slate-50 dark:bg-navy-800 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"/>
                </div>
                <button type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
                    Добавить порог
                </button>
            </div>
        </form>
    </div>

    {{-- Раздел 3: Ссылка на промокоды --}}
    <div>
        <h2 class="text-lg font-semibold text-slate-700 dark:text-navy-100 mb-4">
            <i class="fas fa-ticket-alt mr-2 text-purple-500"></i>Промокоды
        </h2>
        <a href="{{ route('promo-codes.index') }}"
           class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-medium text-sm px-5 py-2.5 rounded-lg shadow-md transition">
            <i class="fas fa-arrow-right"></i>
            Управление промокодами
        </a>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const percentRadio = document.querySelector('input[name="active_type"][value="{{ \App\Models\PromotionSetting::TYPE_PERCENT }}"]');
            const percentInput = document.getElementById('discount_percent');

            const togglePercent = () => {
                const isPercent = percentRadio.checked;
                percentInput.disabled = !isPercent;
                percentInput.classList.toggle('opacity-50', !isPercent);
            };

            document.querySelectorAll('input[name="active_type"]').forEach((item) => {
                item.addEventListener('change', togglePercent);
            });

            togglePercent();
        });
    </script>
@endsection
