@php
    $sourceBadges = [
        'order' => ['text' => '📦 Со страницы заказа', 'class' => 'bg-violet-200 text-violet-800 dark:bg-violet-900 dark:text-violet-200'],
        'support' => ['text' => '💬 Из поддержки', 'class' => 'bg-slate-200 text-slate-700 dark:bg-navy-600 dark:text-slate-200'],
        'bot' => ['text' => '📱 Из бота', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200'],
    ];
    $badge = $sourceBadges[$msg->source] ?? null;
@endphp

@php
    $isDocument = !empty($msg->file_name);
    $isImage = $msg->photo_url && !$isDocument;
    $fileExt = $isDocument ? strtolower(pathinfo($msg->file_name, PATHINFO_EXTENSION)) : '';
    $fileIcon = match(true) {
        $fileExt === 'pdf' => '📄',
        in_array($fileExt, ['doc','docx']) => '📝',
        in_array($fileExt, ['xls','xlsx','csv']) => '📊',
        in_array($fileExt, ['zip','rar','7z']) => '🗜️',
        default => '📎',
    };
@endphp

@if($msg->is_from_user)
    <div class="flex">
        <div class="max-w-xs p-3 bg-slate-200 dark:bg-navy-700 rounded-lg">
            @if($badge)
                <span class="inline-block mb-1 text-[10px] px-1.5 py-0.5 rounded {{ $badge['class'] }}">
                    {{ $badge['text'] }}
                    @if($msg->source === 'bot' && $msg->source_order_id)
                        · заказ №{{ $msg->source_order_id }}
                    @endif
                </span>
                <br>
            @endif
            @if($isImage)
                <a href="{{ $msg->photo_url }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $msg->photo_url }}" alt="Фото" class="mb-2 rounded-lg max-w-full h-auto">
                </a>
            @endif
            @if($isDocument)
                <a href="{{ $msg->photo_url }}" target="_blank" rel="noopener noreferrer"
                   download="{{ $msg->file_name }}"
                   class="flex items-center gap-2 mb-2 p-2 bg-white dark:bg-navy-800 rounded-lg border border-slate-300 dark:border-navy-600 hover:bg-slate-50 dark:hover:bg-navy-600 transition">
                    <span class="text-2xl">{{ $fileIcon }}</span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-medium text-slate-800 dark:text-slate-100 truncate">{{ $msg->file_name }}</span>
                        <span class="block text-xs text-slate-500">Скачать</span>
                    </span>
                </a>
            @endif
            <div class="whitespace-pre-wrap break-words">{{ $msg->text }}</div>
            <div class="mt-1 text-xs text-slate-500">
                {{ $msg->created_at->format('H:i') }}
            </div>
        </div>
    </div>
@else
    <div class="flex justify-end">
        <div class="max-w-xs p-3 bg-emerald-600 text-white rounded-lg">
            @if($badge)
                <span class="inline-block mb-1 text-[10px] px-1.5 py-0.5 rounded bg-white/20 text-white">
                    {{ $badge['text'] }}
                    @if($msg->source === 'order' && $msg->source_order_id)
                        · №{{ $msg->source_order_id }}
                    @endif
                </span>
                <br>
            @endif
            @if($isImage)
                <a href="{{ $msg->photo_url }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $msg->photo_url }}" alt="Фото" class="mb-2 rounded-lg max-w-full h-auto">
                </a>
            @endif
            @if($isDocument)
                <a href="{{ $msg->photo_url }}" target="_blank" rel="noopener noreferrer"
                   download="{{ $msg->file_name }}"
                   class="flex items-center gap-2 mb-2 p-2 bg-emerald-700/40 rounded-lg hover:bg-emerald-700/60 transition">
                    <span class="text-2xl">{{ $fileIcon }}</span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-medium truncate">{{ $msg->file_name }}</span>
                        <span class="block text-xs opacity-80">Скачать</span>
                    </span>
                </a>
            @endif
            <div class="whitespace-pre-wrap break-words">{{ $msg->text }}</div>
            <div class="mt-1 text-xs text-emerald-200">
                Менеджер #{{ $msg->admin_id }} — {{ $msg->created_at->format('H:i') }}
            </div>
        </div>
    </div>
@endif
