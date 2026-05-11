@php
    $fromUser = (bool) $m->is_from_user;
    $sourceLabels = [
        'order' => '📦 Со страницы заказа',
        'support' => '💬 Из поддержки',
        'bot' => '📱 Из бота',
    ];
    $srcLabel = $sourceLabels[$m->source] ?? null;
@endphp
<div class="flex {{ $fromUser ? 'justify-start' : 'justify-end' }}">
    <div class="max-w-[75%] rounded-lg px-4 py-2 text-sm
        {{ $fromUser
            ? 'bg-white dark:bg-navy-700 border border-slate-200 dark:border-navy-600 text-slate-800 dark:text-navy-100'
            : 'bg-blue-600 text-white' }}">
        <div class="text-xs opacity-70 mb-1">
            {{ $fromUser
                ? ('Клиент · ' . ($order->user->first_name ?? 'Пользователь'))
                : ('Поддержка' . ($m->admin ? ' · ' . ($m->admin->name ?? $m->admin->email ?? '') : '')) }}
            · {{ $m->created_at->format('d.m.Y H:i') }}
        </div>
        @if($srcLabel)
            <span class="inline-block mb-1 text-[10px] px-1.5 py-0.5 rounded
                {{ $fromUser
                    ? 'bg-slate-200 text-slate-700 dark:bg-navy-600 dark:text-slate-200'
                    : 'bg-white/20 text-white' }}">
                {{ $srcLabel }}
                @if($m->source_order_id && $m->source_order_id != $order->id)
                    · заказ №{{ $m->source_order_id }}
                @endif
            </span>
        @endif
        <div class="whitespace-pre-wrap break-words">{{ $m->text }}</div>
        @if($m->photo_url)
            @if($m->file_name)
                @php
                    $ext = strtolower(pathinfo($m->file_name, PATHINFO_EXTENSION));
                    $icon = match(true) {
                        $ext === 'pdf' => '📄',
                        in_array($ext, ['doc','docx']) => '📝',
                        in_array($ext, ['xls','xlsx','csv']) => '📊',
                        in_array($ext, ['zip','rar','7z']) => '🗜️',
                        default => '📎',
                    };
                @endphp
                <a href="{{ $m->photo_url }}" target="_blank" rel="noopener noreferrer"
                   download="{{ $m->file_name }}"
                   class="flex items-center gap-2 mt-2 p-2 rounded-lg
                          {{ $fromUser
                                ? 'bg-slate-100 dark:bg-navy-600 hover:bg-slate-200 dark:hover:bg-navy-500 text-slate-800 dark:text-slate-100'
                                : 'bg-white/20 hover:bg-white/30 text-white' }} transition">
                    <span class="text-xl">{{ $icon }}</span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-xs font-medium truncate">{{ $m->file_name }}</span>
                        <span class="block text-[10px] opacity-75">Скачать</span>
                    </span>
                </a>
            @else
                <a href="{{ $m->photo_url }}" target="_blank" class="block mt-2">
                    <img src="{{ $m->photo_url }}" alt="Фото"
                         class="rounded-lg max-w-full h-auto max-h-60 cursor-pointer">
                </a>
            @endif
        @endif
    </div>
</div>
