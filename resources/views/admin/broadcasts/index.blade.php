@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Рассылки</title>
@endsection

@section('content')
    <div class="flex justify-between items-center py-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">Рассылки</h1>
            <p class="text-sm text-slate-500 mt-1">Массовые уведомления пользователям бота</p>
        </div>

        <button onclick="window.location.href='{{ route('broadcasts.create') }}'"
                class="rounded-full bg-indigo-600 px-6 py-2.5 text-white font-medium hover:bg-indigo-700 transition">
            <i class="fas fa-paper-plane mr-1"></i> Создать рассылку
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm bg-white dark:bg-navy-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">ID</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Название</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Статус</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Отправлено</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Ошибки</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-600 dark:text-navy-100">Дата</th>
                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-600 dark:text-navy-100">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-navy-600 text-slate-700 dark:text-navy-100">
                @forelse($broadcasts as $broadcast)
                    <tr class="hover:bg-slate-50 dark:hover:bg-navy-700">
                        <td class="px-4 py-3 text-sm">{{ $broadcast->id }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-sm">{{ $broadcast->title }}</div>
                            <div class="text-xs text-slate-400 truncate max-w-xs">{{ Str::limit($broadcast->message, 60) }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $statusClass = match($broadcast->status) {
                                    'draft' => 'bg-slate-100 text-slate-600',
                                    'sending' => 'bg-amber-100 text-amber-700',
                                    'sent' => 'bg-emerald-100 text-emerald-700',
                                    'failed' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ $broadcast->status_name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm">
                            {{ $broadcast->sent_count }} / {{ $broadcast->total_recipients }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm">
                            @if($broadcast->failed_count > 0)
                                <span class="text-rose-600 font-medium">{{ $broadcast->failed_count }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500">
                            {{ $broadcast->sent_at ? $broadcast->sent_at->format('d.m.Y H:i') : $broadcast->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                @if($broadcast->status === 'draft')
                                    <form action="{{ route('broadcasts.send', $broadcast) }}" method="POST"
                                          onsubmit="return confirm('Отправить рассылку всем активным пользователям?')">
                                        @csrf
                                        <button type="submit" title="Отправить"
                                                class="p-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition">
                                            <i class="fas fa-paper-plane text-sm"></i>
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('broadcasts.destroy', $broadcast) }}" method="POST"
                                      onsubmit="return confirm('Удалить рассылку?')">
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
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                            <i class="fas fa-paper-plane text-3xl mb-2"></i>
                            <p>Рассылки не созданы</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($broadcasts->hasPages())
        <div class="mt-6">
            <x-pagination :paginator="$broadcasts"/>
        </div>
    @endif
@endsection
