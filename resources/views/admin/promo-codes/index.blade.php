@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Промокоды</title>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Промокоды</h1>
            <p class="text-sm text-slate-500 mt-1">Управление скидочными промокодами</p>
        </div>

        <button onclick="window.location.href='{{ route('promo-codes.create') }}'"
                class="rounded-full bg-purple-600 px-6 py-2.5 text-white font-medium hover:bg-purple-700 focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 transition">
            Создать промокод
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Код</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Тип</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Значение</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Мин. сумма</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Использован</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Срок</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Статус</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-navy-600 text-slate-700 dark:text-navy-100">
                @forelse($promoCodes as $code)
                    <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                        <td class="px-4 py-3">
                            <span class="font-mono font-bold text-purple-600 dark:text-purple-400">{{ $code->code }}</span>
                            @if($code->description)
                                <div class="text-xs text-slate-400 mt-0.5">{{ $code->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($code->type === 'percent')
                                <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-xs">Процент</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-green-100 text-green-700 text-xs">Фикс. сумма</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium">
                            {{ $code->value }}{{ $code->type === 'percent' ? '%' : ' руб.' }}
                            @if($code->max_discount)
                                <div class="text-xs text-slate-400">макс. {{ number_format($code->max_discount, 0, '', ' ') }} руб.</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ $code->min_order_amount > 0 ? number_format($code->min_order_amount, 0, '', ' ') . ' руб.' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm">
                            {{ $code->used_count }}{{ $code->usage_limit ? ' / ' . $code->usage_limit : '' }}
                        </td>
                        <td class="px-4 py-3 text-center text-xs">
                            @if($code->starts_at || $code->expires_at)
                                <div>{{ $code->starts_at ? $code->starts_at->format('d.m.Y') : '...' }}</div>
                                <div>{{ $code->expires_at ? $code->expires_at->format('d.m.Y') : '...' }}</div>
                            @else
                                <span class="text-slate-400">Бессрочный</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($code->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                    Активен
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-rose-100 text-rose-700">
                                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                    Неактивен
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                <button title="Редактировать"
                                        onclick="window.location.href='{{ route('promo-codes.edit', $code) }}'"
                                        class="p-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <form action="{{ route('promo-codes.destroy', $code) }}" method="POST"
                                      onsubmit="return confirm('Удалить промокод {{ $code->code }}?')">
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
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                            <i class="fas fa-ticket-alt text-3xl mb-2"></i>
                            <p>Промокоды не созданы</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($promoCodes->hasPages())
        <div class="mt-6">
            <x-pagination :paginator="$promoCodes"/>
        </div>
    @endif
@endsection
