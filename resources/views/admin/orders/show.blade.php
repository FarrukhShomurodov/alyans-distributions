@extends('admin.layouts.app')

@section('title')
    <title>ALYANS DISTRIBUTIONS — Заказ #{{ $order->id }}</title>
@endsection

@section('content')

    <div class="flex justify-between items-center py-6">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-navy-50">
            Заказ №{{ $order->id }}
        </h1>

        <a href="{{ route('orders.index') }}"
           class="rounded-full bg-slate-600 px-6 py-2.5 text-white font-medium hover:bg-slate-700 transition">
            ← Назад
        </a>
    </div>

    <!-- Основная информация -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 🧾 Карточка: Информация о заказе -->
        <div class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4 text-slate-800 dark:text-navy-50">Информация о заказе</h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">ID заказа:</span>
                    <span class="font-medium">{{ $order->id }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Дата создания:</span>
                    <span class="font-medium">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Статус заказа:</span>

                    @php
                        $statusColors = [
                            'new' => 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200',
                            'confirmed' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-800 dark:text-cyan-200',
                            'in_process' => 'bg-amber-100 text-amber-700 dark:bg-amber-800 dark:text-amber-200',
                            'delivery' => 'bg-violet-100 text-violet-700 dark:bg-violet-800 dark:text-violet-200',
                            'done' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200',
                            'canceled' => 'bg-rose-100 text-rose-700 dark:bg-rose-800 dark:text-rose-200',
                        ];
                    @endphp

                    <select id="order-status-select"
                            class="px-3 py-1 rounded-full text-xs font-medium cursor-pointer border-0 outline-none {{ $statusColors[$order->status] ?? '' }}"
                            data-id="{{ $order->id }}">
                        @foreach(\App\Models\Order::getAllStatuses() as $value => $label)
                            <option value="{{ $value }}" {{ $order->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Сумма:</span>
                    <span class="font-semibold text-lg">
                        {{ number_format($order->total, 0, '', ' ') }} руб
                    </span>
                </div>

            </div>
        </div>

        <!-- 👤 Карточка: Информация о клиенте -->
        <div class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4 text-slate-800 dark:text-navy-50">Покупатель</h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Имя:</span>
                    <span class="font-medium">{{ $order->user->first_name ?? '—' }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Фамилия:</span>
                    <span class="font-medium">{{ $order->user->second_name ?? '—' }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Телефон:</span>
                    <span class="font-medium">{{ $order->user->phone ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- 💳 Карточка: Оплата -->
        <div class="bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4 text-slate-800 dark:text-navy-50">Оплата</h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Тип оплаты:</span>
                    <span class="font-medium text-slate-800 dark:text-navy-100">
                        {{ strtoupper($order->payment_type) }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Статус:</span>

                    <span class="px-3 py-1 rounded-full text-xs font-medium
                        {{ $order->payment_status === 'paid'
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200'
                            : 'bg-slate-200 text-slate-600 dark:bg-navy-600 dark:text-slate-300' }}">
                        {{ strtoupper($order->payment_status) }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- 🛒 Товары заказа -->
    <div class="mt-10 bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm">
        <div class="p-6 border-b border-slate-200 dark:border-navy-600">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-navy-50">Товары</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-navy-600">
                <thead class="bg-slate-100 dark:bg-navy-700">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold">#</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Название</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Цена</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Кол-во</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Всего</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 dark:divide-navy-600">
                @foreach($order->items as $item)
                    <tr class="hover:bg-slate-50 dark:hover:bg-navy-700 transition">
                        <td class="px-4 py-3 text-sm">{{ $item->id }}</td>
                        <td class="px-4 py-3 text-sm font-medium">
                            {{ $item->product->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ number_format($item->price, 0, '', ' ') }} руб
                        </td>
                        <td class="px-4 py-3 text-sm">
                            {{ $item->quantity }}
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold">
                            {{ number_format($item->price * $item->quantity, 0, '', ' ') }} руб
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>

    <!-- 💬 Чат с клиентом (поддержка) -->
    <div class="mt-10 bg-white dark:bg-navy-800 rounded-xl border border-slate-200 dark:border-navy-600 shadow-sm">
        <div class="p-6 border-b border-slate-200 dark:border-navy-600 flex items-center justify-between flex-wrap gap-2">
            <div>
                <h2 class="text-lg font-semibold text-slate-800 dark:text-navy-50">
                    💬 Чат с клиентом (поддержка)
                </h2>
                @if($chat)
                    <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full
                        {{ $chat->status === 'closed'
                            ? 'bg-slate-200 text-slate-600'
                            : 'bg-emerald-100 text-emerald-700' }}">
                        Статус: {{ $chat->status === 'closed' ? 'Закрыт' : 'Открыт' }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($order->user && $order->user->chat_id)
                    <span class="text-xs text-slate-500">
                        Telegram ID: {{ $order->user->chat_id }}
                        @if($order->user->uname)
                            {{ '@'.$order->user->uname }}
                        @endif
                    </span>
                @else
                    <span class="text-xs text-rose-500">
                        Нет привязанного Telegram
                    </span>
                @endif
                @if($chat && $chat->status !== 'closed')
                    <form method="POST" action="{{ route('orders.close-chat', $order) }}"
                          onsubmit="return confirm('Закрыть чат с клиентом?');">
                        @csrf
                        <button type="submit"
                                class="text-xs px-3 py-1.5 rounded-full bg-rose-100 text-rose-700 hover:bg-rose-200 transition">
                            Закрыть чат
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- История сообщений (с авто-обновлением каждые 5 сек) --}}
            <div id="order-chat-messages"
                 class="mb-5 max-h-96 overflow-y-auto space-y-3 p-3 rounded-lg bg-slate-50 dark:bg-navy-900 border border-slate-200 dark:border-navy-600 {{ $messages->isEmpty() ? 'hidden' : '' }}"
                 data-order-id="{{ $order->id }}"
                 data-last-id="{{ $messages->isNotEmpty() ? $messages->last()->id : 0 }}">
                @foreach($messages as $m)
                    @include('admin.orders._chat_message', ['m' => $m, 'order' => $order])
                @endforeach
            </div>

            @if($messages->isEmpty())
                <p id="order-chat-empty" class="text-sm text-slate-400 mb-5 italic">
                    Пока нет сообщений. Напишите клиенту первым, чтобы начать переписку.
                </p>
            @endif

            {{-- Форма отправки --}}
            @if($order->user && $order->user->chat_id)
                <form method="POST" action="{{ route('orders.send-message', $order) }}"
                      enctype="multipart/form-data" id="orderChatSendForm" class="space-y-3">
                    @csrf
                    <div class="flex items-end gap-2">
                        @include('admin.partials._chat_templates_picker', ['textareaId' => 'orderChatTextarea'])
                        <label for="order-attach"
                               class="cursor-pointer flex items-center justify-center w-11 h-11 flex-shrink-0 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-navy-700 dark:hover:bg-navy-600 text-slate-600 dark:text-slate-300 transition"
                               title="Прикрепить фото / PDF / документ">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                            </svg>
                            <input type="file" id="order-attach" name="attachment"
                                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.rar"
                                   class="hidden"
                                   onchange="
                                       const f = this.files[0];
                                       const p = document.getElementById('order-attach-name');
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
                                  id="orderChatTextarea"
                                  data-templates-trigger="1"
                                  rows="1"
                                  maxlength="4000"
                                  placeholder="Сообщение клиенту… (введите / для шаблонов, Shift+Enter — новая строка)"
                                  style="resize:none;max-height:200px;min-height:44px"
                                  class="flex-1 rounded-lg border border-slate-300 dark:border-navy-600 dark:bg-navy-700 dark:text-navy-100 p-3 text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,200)+'px'"
                                  onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();document.getElementById('orderChatSendForm').requestSubmit();}"></textarea>
                        <button type="submit"
                                class="flex items-center justify-center w-11 h-11 flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                                title="Отправить (Enter)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                    </div>

                    <div id="order-attach-name" class="hidden text-xs text-slate-500 px-1"></div>

                    <div class="text-xs text-slate-500">
                        Клиент получит сообщение/фото в Telegram и сможет ответить прямо там.
                    </div>
                </form>
            @else
                <p class="text-sm text-slate-500">
                    Невозможно отправить сообщение: у клиента отсутствует Telegram chat_id.
                </p>
            @endif
        </div>
    </div>

    <script>
        const statusSelect = document.getElementById('order-status-select');
        if (statusSelect) {
            statusSelect.dataset.prev = statusSelect.value;

            statusSelect.addEventListener('change', function () {
                const orderId = this.dataset.id;
                const newStatus = this.value;
                const oldStatus = this.dataset.prev;
                const self = this;

                const labels = {new: 'Новый', in_process: 'В обработке', done: 'Выполнен', canceled: 'Отменён'};

                if (newStatus === 'canceled' || newStatus === 'done') {
                    if (!confirm(`Изменить статус заказа №${orderId} на "${labels[newStatus]}"?`)) {
                        self.value = oldStatus;
                        return;
                    }
                }

                self.disabled = true;

                fetch(`/dashboard/orders/${orderId}/status`, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({status: newStatus})
                })
                    .then(r => r.json())
                    .then(data => {
                        self.disabled = false;
                        if (!data.success) {
                            alert(data.message || 'Ошибка при смене статуса');
                            self.value = oldStatus;
                            return;
                        }

                        self.dataset.prev = newStatus;

                        const colors = {
                            new: "bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200",
                            in_process: "bg-amber-100 text-amber-700 dark:bg-amber-800 dark:text-amber-200",
                            done: "bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200",
                            canceled: "bg-rose-100 text-rose-700 dark:bg-rose-800 dark:text-rose-200",
                        };

                        self.className = "px-3 py-1 rounded-full text-xs font-medium cursor-pointer border-0 outline-none " + colors[newStatus];

                    })
                    .catch(() => {
                        self.disabled = false;
                        self.value = oldStatus;
                        alert('Ошибка сети');
                    });
            });
        }
    </script>

    {{-- Авто-обновление чата заказа каждые 5 секунд (поллинг) --}}
    <script>
        (function () {
            const messagesEl = document.getElementById('order-chat-messages');
            if (!messagesEl) return;

            const orderId = messagesEl.dataset.orderId;
            let lastId = parseInt(messagesEl.dataset.lastId || 0, 10);
            const pollUrl = "{{ route('orders.chat-poll', ['order' => '__ID__']) }}".replace('__ID__', orderId);

            // Скроллим вниз при загрузке (если уже есть сообщения)
            if (!messagesEl.classList.contains('hidden')) {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }

            // Звуковой сигнал при новом сообщении
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
                        // Если контейнер скрыт (не было сообщений) — показываем
                        messagesEl.classList.remove('hidden');
                        const emptyPlaceholder = document.getElementById('order-chat-empty');
                        if (emptyPlaceholder) emptyPlaceholder.remove();

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

                        if (wasAtBottom) {
                            messagesEl.scrollTop = messagesEl.scrollHeight;
                        }

                        playPing();
                    }
                } catch (err) {
                    console.warn('[order-chat poll] error:', err);
                } finally {
                    inFlight = false;
                }
            }

            const POLL_INTERVAL = 5000;
            setInterval(poll, POLL_INTERVAL);

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') poll();
            });
        })();
    </script>

@endsection
