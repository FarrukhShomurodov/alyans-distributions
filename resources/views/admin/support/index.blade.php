@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Поддержка</title>
@endsection

@section('content')

    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold">Чаты поддержки</h1>
    </div>

    {{-- ФИЛЬТРЫ --}}
    <form method="GET" action="{{ route('support.index') }}"
          class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 p-4 mb-5 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3">

            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Поиск</label>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Имя, фамилия, @username, телефон, TG ID"
                       class="w-full rounded-lg border border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">№ заказа</label>
                <input type="number" name="order_id" value="{{ $orderId }}"
                       placeholder="Напр. 55"
                       class="w-full rounded-lg border border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Тип</label>
                <select name="type"
                        class="w-full rounded-lg border border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Все</option>
                    <option value="support" {{ $type === 'support' ? 'selected' : '' }}>💬 Поддержка</option>
                    <option value="order" {{ $type === 'order' ? 'selected' : '' }}>📦 По заказу</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Статус</label>
                <select name="status"
                        class="w-full rounded-lg border border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Все</option>
                    <option value="new" {{ $status === 'new' ? 'selected' : '' }}>Новый</option>
                    <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Открыт</option>
                    <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Закрыт</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Дата с</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="w-full rounded-lg border border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="lg:col-span-5">
                <label class="block text-xs font-medium text-slate-500 mb-1">Дата по</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="w-full rounded-lg border border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm font-medium transition">
                    Применить
                </button>
                <a href="{{ route('support.index') }}"
                   class="rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-navy-700 dark:hover:bg-navy-600 text-slate-800 dark:text-slate-700 px-4 py-2 text-sm font-medium transition">
                    Сброс
                </a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border shadow bg-white dark:bg-navy-800 dark:border-navy-600">

        <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
            <thead class="bg-slate-100 dark:bg-navy-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold">Пользователь</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Тип</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Последнее сообщение</th>
                <th class="px-4 py-3 text-left text-sm font-semibold">Статус</th>
                <th class="px-4 py-3 text-center text-sm font-semibold">Дата</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
            @forelse($chats as $chat)
                @php
                    $unread = (int) ($chat->unread_count ?? 0);
                    $lastMsg = $chat->lastMessage;
                    $lastFromUser = $lastMsg ? (bool) $lastMsg->is_from_user : null;
                @endphp
                <tr onclick="location.href='{{ route('support.show', $chat->id) }}'"
                    class="cursor-pointer transition hover:bg-slate-50 dark:hover:bg-navy-700"
                    style="{{ $unread > 0 ? 'background-color: #f8faff; border-left: 3px solid #3b82f6;' : 'border-left: 3px solid transparent;' }}">

                    {{-- Пользователь --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @if($unread > 0)
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:#3b82f6" title="Есть непрочитанные"></span>
                            @else
                                <span class="w-2 h-2 flex-shrink-0"></span>
                            @endif
                            <div class="min-w-0">
                                <div style="font-size:14px; color:#0f172a; {{ $unread > 0 ? 'font-weight:600' : 'font-weight:500' }}">
                                    {{ trim(($chat->user->first_name ?? '') . ' ' . ($chat->user->second_name ?? '')) ?: 'Пользователь' }}
                                </div>
                                @if($chat->user->uname)
                                    <div style="font-size:11px; color:#64748b">
                                        {{ '@' . $chat->user->uname }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Тип чата --}}
                    <td class="px-4 py-3">
                        @if($chat->order_id)
                            <span style="display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:#f3e8ff;color:#6b21a8;border:1px solid #d8b4fe">
                                <span style="width:6px;height:6px;border-radius:999px;background:#a855f7"></span>
                                Заказ №{{ $chat->order_id }}
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:#f1f5f9;color:#475569;border:1px solid #cbd5e1">
                                <span style="width:6px;height:6px;border-radius:999px;background:#94a3b8"></span>
                                Поддержка
                            </span>
                        @endif
                    </td>

                    {{-- Последнее сообщение --}}
                    <td class="px-4 py-3 max-w-md">
                        <div style="display:flex;align-items:baseline;gap:6px;font-size:14px">
                            @if($lastFromUser === true)
                                <span style="color:#2563eb;font-weight:500;font-size:12px;white-space:nowrap" title="Сообщение от клиента">
                                    Клиент:
                                </span>
                            @elseif($lastFromUser === false)
                                <span style="color:#64748b;font-weight:500;font-size:12px;white-space:nowrap" title="Ответ менеджера">
                                    Вы:
                                </span>
                            @endif
                            <span class="truncate" style="color:{{ $unread > 0 ? '#1e293b;font-weight:500' : '#64748b' }}">
                                {{ Str::limit($lastMsg->text ?? '—', 70) }}
                            </span>
                        </div>
                    </td>

                    {{-- Статус и счётчик --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div style="display:flex;align-items:center;gap:6px">
                            @if($unread > 0)
                                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;background:#3b82f6;color:#fff;border-radius:999px;font-size:11px;font-weight:600"
                                      title="Непрочитанных сообщений">
                                    {{ $unread > 99 ? '99+' : $unread }}
                                </span>
                            @endif
                            @if($chat->status === 'new')
                                <span style="display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:#fef3c7;color:#92400e;border:1px solid #fcd34d">
                                    <span style="width:6px;height:6px;border-radius:999px;background:#f59e0b"></span>
                                    Новый
                                </span>
                            @elseif($chat->status === 'open')
                                <span style="display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:#dcfce7;color:#166534;border:1px solid #86efac">
                                    <span style="width:6px;height:6px;border-radius:999px;background:#22c55e"></span>
                                    Открыт
                                </span>
                            @else
                                <span style="display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:#f1f5f9;color:#64748b;border:1px solid #cbd5e1">
                                    Закрыт
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Дата --}}
                    <td class="px-4 py-3 text-center whitespace-nowrap" style="font-size:12px;color:#64748b">
                        {{ $chat->updated_at->format('d.m.Y') }}<br>
                        <span style="color:#94a3b8">{{ $chat->updated_at->format('H:i') }}</span>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-400">
                        Чаты не найдены. Попробуйте изменить фильтры.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $chats->links() }}
    </div>

@endsection
