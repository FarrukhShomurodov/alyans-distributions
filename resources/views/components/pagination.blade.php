@props(['paginator'])

@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $window = 2; // показывать по 2 страницы слева и справа от текущей
        $start = max(1, $current - $window);
        $end = min($last, $current + $window);

        // Если начало окна близко к 1 — расширяем вправо
        if ($start <= 3) {
            $start = 1;
            $end = min($last, 1 + $window * 2);
        }

        // Если конец окна близко к последней — расширяем влево
        if ($end >= $last - 2) {
            $end = $last;
            $start = max(1, $last - $window * 2);
        }
    @endphp

    <div class="mt-6 px-4 flex justify-center">
        <nav class="inline-flex items-center space-x-1" role="navigation" aria-label="Pagination">
            {{-- Назад --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1 text-gray-400 bg-gray-200 rounded-md cursor-default">&laquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">&laquo;</a>
            @endif

            {{-- Первая страница + многоточие --}}
            @if ($start > 1)
                <a href="{{ $paginator->url(1) }}"
                   class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">1</a>
                @if ($start > 2)
                    <span class="px-2 py-1 text-gray-400">...</span>
                @endif
            @endif

            {{-- Номера страниц (окно) --}}
            @foreach ($paginator->getUrlRange($start, $end) as $page => $url)
                @if ($page == $current)
                    <span class="px-3 py-1 bg-blue-700 text-white rounded-md font-semibold">{{ $page }}</span>
                @else
                    <a href="{{ $url }}"
                       class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Многоточие + последняя страница --}}
            @if ($end < $last)
                @if ($end < $last - 1)
                    <span class="px-2 py-1 text-gray-400">...</span>
                @endif
                <a href="{{ $paginator->url($last) }}"
                   class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">{{ $last }}</a>
            @endif

            {{-- Вперёд --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">&raquo;</a>
            @else
                <span class="px-3 py-1 text-gray-400 bg-gray-200 rounded-md cursor-default">&raquo;</span>
            @endif
        </nav>
    </div>
@endif
