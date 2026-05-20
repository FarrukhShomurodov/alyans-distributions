@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Чат поддержки</title>
@endsection

@section('content')

    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold">
            Чат с {{ $chat->user->first_name ?? 'пользователем' }}
            @if($chat->user->second_name)
                {{ $chat->user->second_name }}
            @endif
        </h1>
        <a href="{{ route('support.index') }}"
           class="rounded-full bg-slate-600 px-5 py-2 text-white text-sm hover:bg-slate-700 transition">
            ← К списку чатов
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- CHAT -->
        <div class="col-span-2">

            <div class="rounded-xl border shadow bg-white dark:bg-navy-800 dark:border-navy-600 h-[70vh] flex flex-col">

                <div id="messages"
                     class="flex-1 overflow-y-auto p-4 space-y-4"
                     data-chat-id="{{ $chat->id }}"
                     data-last-id="{{ $chat->messages->isNotEmpty() ? $chat->messages->last()->id : 0 }}">
                    @forelse($chat->messages as $msg)
                        @include('admin.support._message', ['msg' => $msg])
                    @empty
                        <p class="text-sm text-slate-400 italic text-center" id="empty-placeholder">
                            Пока нет сообщений
                        </p>
                    @endforelse
                </div>

                @if($chat->status !== 'closed')
                    <form method="POST"
                          action="{{ route('support.send', $chat->id) }}"
                          enctype="multipart/form-data"
                          id="supportSendForm"
                          class="p-4 border-t dark:border-navy-600 space-y-2">
                        @csrf
                        <div class="flex items-end gap-2">
                            @include('admin.partials._chat_templates_picker', ['textareaId' => 'supportTextarea'])
                            <label for="support-attach"
                                   class="cursor-pointer flex items-center justify-center w-11 h-11 flex-shrink-0 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-navy-700 dark:hover:bg-navy-600 text-slate-600 dark:text-slate-300 transition"
                                   title="Прикрепить фото / PDF / документ">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                                </svg>
                                <input type="file" id="support-attach" name="attachment"
                                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.rar"
                                       class="hidden"
                                       onchange="
                                           const f = this.files[0];
                                           const p = document.getElementById('attach-preview');
                                           if (f) {
                                               p.textContent = '📎 ' + f.name + ' (' + (f.size/1024).toFixed(0) + ' КБ)';
                                               p.classList.remove('hidden');
                                           } else {
                                               p.textContent = '';
                                               p.classList.add('hidden');
                                           }
                                       ">
                            </label>
                            <textarea name="text"
                                      id="supportTextarea"
                                      data-templates-trigger="1"
                                      rows="1"
                                      placeholder="Написать... (введите / для шаблонов, Shift+Enter — новая строка)"
                                      style="resize:none;max-height:200px;min-height:44px"
                                      class="flex-1 rounded-lg border p-3 dark:bg-navy-700 dark:border-navy-600 text-sm leading-relaxed"
                                      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,200)+'px'"
                                      onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();document.getElementById('supportSendForm').requestSubmit();}"></textarea>
                            <button type="submit"
                                    class="flex items-center justify-center w-11 h-11 flex-shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition"
                                    title="Отправить (Enter)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                            </button>
                        </div>
                        <div id="attach-preview" class="hidden text-xs text-slate-500 px-1"></div>
                    </form>
                @endif
            </div>

            @if($chat->status !== 'closed')
                <form method="POST" action="{{ route('support.close', $chat->id) }}" class="mt-3">
                    @csrf
                    <button class="px-5 py-2 bg-red-600 text-white rounded-lg">Закрыть чат</button>
                </form>
            @endif

        </div>

        <!-- SIDEBAR: User info + orders history -->
        <div class="col-span-1 space-y-6">

            <!-- Информация о пользователе -->
            <div class="rounded-xl border p-5 dark:border-navy-600 bg-white dark:bg-navy-800">

                <h2 class="text-xl font-semibold mb-4">👤 Пользователь</h2>

                <div class="space-y-2 text-sm">
                    <p><strong class="text-slate-500">Имя:</strong>
                        <span class="font-medium">{{ $chat->user->first_name ?? '—' }}</span>
                    </p>
                    <p><strong class="text-slate-500">Фамилия:</strong>
                        <span class="font-medium">{{ $chat->user->second_name ?? '—' }}</span>
                    </p>
                    @if($chat->user->uname)
                        <p><strong class="text-slate-500">Username:</strong>
                            <span class="font-medium">@{{ $chat->user->uname }}</span>
                        </p>
                    @endif
                    <p><strong class="text-slate-500">Язык:</strong>
                        <span class="font-medium">{{ $chat->user->lang ?? '—' }}</span>
                    </p>
                    <p><strong class="text-slate-500">Телефон:</strong>
                        <span class="font-medium">{{ $chat->user->phone ?? '—' }}</span>
                    </p>
                    @if($chat->user->saved_email)
                        <p><strong class="text-slate-500">Email:</strong>
                            <span class="font-medium">{{ $chat->user->saved_email }}</span>
                        </p>
                    @endif
                    <p><strong class="text-slate-500">Telegram ID:</strong>
                        <span class="font-medium">{{ $chat->user->chat_id }}</span>
                    </p>
                    <p><strong class="text-slate-500">Зарегистрирован:</strong>
                        <span class="font-medium">{{ $chat->user->created_at?->format('d.m.Y') ?? '—' }}</span>
                    </p>
                </div>

                <a href="{{ route('bot.users.show', $chat->user->id) }}"
                   class="inline-block mt-4 text-sm text-blue-600 hover:underline">
                    Открыть профиль →
                </a>
            </div>

            <!-- Контекст текущего заказа (если чат привязан) -->
            @if($chat->order)
                <div class="rounded-xl border p-5 dark:border-navy-600 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-700">
                    <h2 class="text-sm font-semibold mb-3 text-amber-800 dark:text-amber-200">
                        📌 Текущий контекст
                    </h2>
                    <p class="text-sm">
                        Чат связан с
                        <a href="{{ route('orders.show', $chat->order->id) }}"
                           class="font-semibold text-blue-600 hover:underline">
                            заказом №{{ $chat->order->id }}
                        </a>
                    </p>
                </div>
            @endif

            <!-- История заказов -->
            <div class="rounded-xl border dark:border-navy-600 bg-white dark:bg-navy-800">
                <div class="p-5 border-b dark:border-navy-600 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">📦 История заказов</h2>
                    <span class="text-xs text-slate-500">{{ $orders->count() }} всего</span>
                </div>

                <div class="max-h-[500px] overflow-y-auto divide-y divide-slate-200 dark:divide-navy-600">
                    @forelse($orders as $o)
                        @php
                            $statusColors = [
                                'new' => 'bg-blue-100 text-blue-700',
                                'confirmed' => 'bg-cyan-100 text-cyan-700',
                                'in_process' => 'bg-amber-100 text-amber-700',
                                'delivery' => 'bg-violet-100 text-violet-700',
                                'done' => 'bg-emerald-100 text-emerald-700',
                                'canceled' => 'bg-rose-100 text-rose-700',
                            ];
                        @endphp
                        <a href="{{ route('orders.show', $o->id) }}"
                           class="block p-4 hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                            <div class="flex justify-between items-start mb-2">
                                <div class="font-semibold text-sm">
                                    Заказ №{{ $o->id }}
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $statusColors[$o->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $o->status_name ?? $o->status }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-500 mb-1">
                                {{ $o->created_at->format('d.m.Y H:i') }}
                            </div>
                            <div class="text-xs text-slate-600 dark:text-slate-400 mb-1">
                                {{ $o->items->count() }} поз. ·
                                <span class="font-semibold text-slate-800 dark:text-slate-100">
                                    {{ number_format($o->total, 0, '', ' ') }} сум
                                </span>
                            </div>
                            @if($o->delivery_address)
                                <div class="text-xs text-slate-500 truncate">
                                    📍 {{ $o->delivery_address }}
                                </div>
                            @endif
                        </a>
                    @empty
                        <p class="p-4 text-sm text-slate-400 italic">
                            У клиента нет заказов
                        </p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    {{-- Авто-обновление чата каждые 5 секунд (поллинг) --}}
    <script>
        (function () {
            const messagesEl = document.getElementById('messages');
            if (!messagesEl) return;

            const chatId = messagesEl.dataset.chatId;
            let lastId = parseInt(messagesEl.dataset.lastId || 0, 10);
            const pollUrl = "{{ route('support.poll', ['chat' => '__ID__']) }}".replace('__ID__', chatId);

            // Скроллим вниз при загрузке страницы
            messagesEl.scrollTop = messagesEl.scrollHeight;

            // Звук уведомления при новом сообщении (короткий beep через WebAudio)
            function playPing() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.frequency.value = 880;
                    gain.gain.setValueAtTime(0.05, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);
                    osc.connect(gain).connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.2);
                } catch (e) {}
            }

            let inFlight = false;
            async function poll() {
                if (inFlight) return;
                inFlight = true;
                try {
                    const res = await fetch(pollUrl + '?since_id=' + lastId, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();

                    if (data.count > 0 && Array.isArray(data.messages_html)) {
                        // Убираем плейсхолдер "Пока нет сообщений" если он есть
                        const placeholder = document.getElementById('empty-placeholder');
                        if (placeholder) placeholder.remove();

                        // Запоминаем — был ли скролл внизу до добавления
                        const wasAtBottom = (messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight) < 80;

                        data.messages_html.forEach(html => {
                            const tmp = document.createElement('div');
                            tmp.innerHTML = html.trim();
                            while (tmp.firstChild) {
                                messagesEl.appendChild(tmp.firstChild);
                            }
                        });

                        lastId = data.last_id;
                        messagesEl.dataset.lastId = lastId;

                        // Автоскролл если был внизу или мы сами писали
                        if (wasAtBottom) {
                            messagesEl.scrollTop = messagesEl.scrollHeight;
                        }

                        playPing();
                    }
                } catch (err) {
                    console.warn('[chat poll] error:', err);
                } finally {
                    inFlight = false;
                }
            }

            // Старт поллинга — каждые 5 секунд
            const POLL_INTERVAL = 5000;
            const intervalId = setInterval(poll, POLL_INTERVAL);

            // Также обновляем мгновенно при возвращении вкладки в фокус
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') poll();
            });
        })();

        // Глобальный поллинг счётчика непрочитанных в сайдбаре
        (function () {
            const badge = document.querySelector('a[href="{{ route('support.index') }}"] span.bg-red-500');
            if (!badge) return;

            async function refreshBadge() {
                try {
                    const res = await fetch("{{ route('support.unread-count') }}", {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (typeof data.count === 'number') {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = data.count > 0 ? '' : 'none';
                    }
                } catch (e) {}
            }

            setInterval(refreshBadge, 15000);
        })();
    </script>

@endsection
