@php
    $hasChildren = ($category->childrenRecursive ?? collect())->isNotEmpty();
    $indent = $level * 32;
    $levelColors = [
        0 => 'border-l-blue-500',
        1 => 'border-l-emerald-500',
        2 => 'border-l-amber-500',
    ];
    $borderColor = $levelColors[$level] ?? 'border-l-slate-400';
@endphp

<div class="category-row">
    <div class="flex items-center justify-between px-4 py-3 hover:bg-slate-50 dark:hover:bg-navy-700 transition border-l-4 {{ $borderColor }}"
         style="padding-left: {{ $indent + 16 }}px;">

        <div class="flex items-center gap-3 flex-1 min-w-0">
            @if($hasChildren)
                <button onclick="toggleChildren({{ $category->id }})"
                        class="w-6 h-6 flex items-center justify-center rounded hover:bg-slate-200 dark:hover:bg-navy-600 transition flex-shrink-0">
                    <svg id="toggle-icon-{{ $category->id }}"
                         class="w-4 h-4 text-slate-500 transition-transform duration-200 rotate-90"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <span class="w-6 h-6 flex items-center justify-center flex-shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-navy-500"></span>
                </span>
            @endif

            @if($category->photo_url)
                <img src="{{ asset('storage/'.$category->photo_url) }}"
                     class="w-8 h-8 rounded-lg object-cover border border-slate-200 dark:border-navy-600 flex-shrink-0"
                     alt="">
            @else
                <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-navy-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-folder text-slate-400 dark:text-navy-300 text-sm"></i>
                </span>
            @endif

            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-sm text-slate-800 dark:text-navy-50 truncate">{{ $category->name }}</span>
                    @if($level === 0)
                        <span class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 flex-shrink-0">
                            Корневая
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 flex-shrink-0 ml-4">
            @if($category->discount_percent)
                <span class="inline-block px-2 py-0.5 text-xs rounded bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200">
                    -{{ $category->discount_percent }}%
                </span>
            @endif

            @if($hasChildren)
                <span class="text-xs text-slate-400" title="Подкатегории">
                    <i class="fas fa-sitemap mr-1"></i>{{ $category->childrenRecursive->count() }}
                </span>
            @endif

            @if($category->products_count ?? false)
                <span class="text-xs text-slate-400" title="Товары">
                    <i class="fas fa-box mr-1"></i>{{ $category->products_count }}
                </span>
            @endif

            @if($category->is_active)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-200">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                    Активна
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-rose-100 text-rose-700 dark:bg-rose-800 dark:text-rose-200">
                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                    Неактивна
                </span>
            @endif

            <div class="flex items-center gap-1">
                <button title="Редактировать"
                        onclick="window.location.href='{{ route('categories.edit', $category->id) }}'"
                        class="p-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>

                <form action="{{ route('categories.destroy', $category->id) }}" method="POST"
                      onsubmit="return confirm('Удалить категорию {{ $category->name }}?{{ $hasChildren ? ' Все подкатегории тоже будут удалены!' : '' }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Удалить"
                            class="p-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if($hasChildren)
        <div id="children-{{ $category->id }}" class="">
            @foreach($category->childrenRecursive as $child)
                @include('admin.categories.partials.category-tree-row', ['category' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
