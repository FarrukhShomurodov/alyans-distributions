@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Шаблоны ответов</title>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Шаблоны ответов</h1>
            <p class="text-sm text-slate-500 mt-1">Готовые ответы для чатов поддержки и заказов. Менеджер вводит <code class="bg-slate-100 px-1 rounded">/команда</code> и получает шаблон.</p>
        </div>
        <a href="{{ route('chat-templates.create') }}"
           class="rounded-full bg-blue-600 px-5 py-2.5 text-white font-medium hover:bg-blue-700 transition text-sm">
            + Добавить шаблон
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 w-16">#</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Команда</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Название</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600">Текст</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 w-24">Порядок</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 w-24">Статус</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 w-32">Действия</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
            @forelse($templates as $i => $t)
                <tr class="hover:bg-slate-50 dark:hover:bg-navy-700">
                    <td class="px-4 py-3 text-sm text-slate-500">{{ $i + 1 }}</td>
                    <td class="px-4 py-3">
                        <code class="bg-slate-100 dark:bg-navy-700 px-2 py-0.5 rounded text-sm text-blue-700 dark:text-blue-300">/{{ $t->command }}</code>
                    </td>
                    <td class="px-4 py-3 text-sm font-medium">{{ $t->title }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 max-w-md truncate" title="{{ $t->text }}">
                        {{ \Illuminate\Support\Str::limit($t->text, 80) }}
                    </td>
                    <td class="px-4 py-3 text-center text-sm">{{ $t->sort_order }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($t->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Активен
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-slate-200 text-slate-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                Скрыт
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('chat-templates.edit', $t->id) }}"
                               title="Редактировать"
                               class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                                ✏️
                            </a>
                            <form method="POST" action="{{ route('chat-templates.destroy', $t->id) }}"
                                  onsubmit="return confirm('Удалить шаблон «{{ $t->title }}»?');"
                                  class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" title="Удалить"
                                        class="p-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition">
                                    🗑
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">
                        Шаблонов пока нет. Нажмите «Добавить шаблон» чтобы создать первый.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
