@php
    $hasChildren = $cat->relationLoaded('children')
        ? $cat->children->count() > 0
        : ($cat->relationLoaded('childrenRecursive') && $cat->childrenRecursive->count() > 0);

    $isActive = isset($currentCategoryId) && (int) $currentCategoryId === (int) $cat->id;
    $level = $level ?? 0;

    $children = collect();
    if ($cat->relationLoaded('childrenRecursive')) {
        $children = $cat->childrenRecursive;
    } elseif ($cat->relationLoaded('children')) {
        $children = $cat->children;
    }

    // Эмодзи для иконки если нет фото
    $defaultEmoji = $level === 0 ? '🗂️' : '📦';
@endphp

@if($hasChildren && $children->count() > 0)
    <div class="category-modal__row" data-cat-id="{{ $cat->id }}">
        <a href="{{ route('webapp.category.products', ['category' => $cat, 'chat_id' => request('chat_id')]) }}"
           class="category-modal__item {{ $isActive ? 'active' : '' }}" style="flex:1;min-width:0">
            <span class="category-modal__item-icon">
                @if($cat->photo_url)
                    <img src="{{ asset('storage/' . $cat->photo_url) }}" alt="">
                @else
                    {{ $defaultEmoji }}
                @endif
            </span>
            <span class="category-modal__item-name">{{ $cat->name }}</span>
        </a>
        <button type="button" class="category-modal__expand js-cat-expand" data-cat-id="{{ $cat->id }}"
                aria-label="Раскрыть подкатегории">
            <i data-lucide="chevron-down"></i>
        </button>
    </div>
    <div class="category-modal__children" id="cat-children-{{ $cat->id }}" style="display:none">
        @foreach($children as $child)
            @include('webapp.partials._category-modal-item', [
                'cat' => $child,
                'level' => $level + 1,
                'currentCategoryId' => $currentCategoryId ?? null,
            ])
        @endforeach
    </div>
@else
    <a href="{{ route('webapp.category.products', ['category' => $cat, 'chat_id' => request('chat_id')]) }}"
       class="category-modal__item {{ $isActive ? 'active' : '' }}">
        <span class="category-modal__item-icon">
            @if($cat->photo_url)
                <img src="{{ asset('storage/' . $cat->photo_url) }}" alt="">
            @else
                {{ $defaultEmoji }}
            @endif
        </span>
        <span class="category-modal__item-name">{{ $cat->name }}</span>
        <span class="category-modal__item-arrow">›</span>
    </a>
@endif
