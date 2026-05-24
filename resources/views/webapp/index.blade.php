@extends('webapp.layout')

@section('content')
    {{-- TOP HEADER --}}
    <header class="top-header">
        <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}" class="top-header__burger" aria-label="Назад"><i data-lucide="arrow-left"></i></a>
        <form action="{{ url()->current() }}" method="get" class="header-search" role="search">
            <span class="header-search__icon"><i data-lucide="search" style="width:18px;height:18px"></i></span>
            <input type="text" name="query" class="header-search__input"
                   placeholder="{{ __('webapp.search_placeholder') }}"
                   value="{{ $query ?? request('query') }}"
                   autocomplete="off">
        </form>
    </header>

    {{-- HORIZONTAL CATEGORY ICONS --}}
    @if(($mainCategories ?? collect())->count())
        @php
            $catEmojis = [
                'Акции' => '🏷️', 'Инструменты' => '🧰', 'Крепёж' => '🔩',
                'Сухие смеси' => '🧱', 'Отделочные материалы' => '🎨',
                'Сантехника' => '🚿', 'Электрика' => '💡',
                'Напольные покрытия' => '🪵', 'Гипсокартон и профиль' => '📐',
                'Изоляция' => '🧊', 'Двери и окна' => '🚪',
                'Спецодежда и СИЗ' => '🦺', 'Сад и участок' => '🌳',
                'Электроинструмент' => '⚡', 'Ручной инструмент' => '🔨',
                'Оснастка и расходники' => '⚙️',
                'Цемент и бетон' => '🧱', 'Штукатурка и шпаклёвка' => '🪣',
                'Клей плиточный' => '🧴',
                'Краски и грунтовки' => '🎨', 'Обои' => '📜', 'Плитка и керамогранит' => '◼️',
            ];
        @endphp
        <div class="cat-icons" role="navigation" aria-label="Категории">
            <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}" class="cat-icon cat-icon--all">
                <span class="cat-icon__img-wrap">
                    <i data-lucide="layout-grid" style="width:22px;height:22px;color:var(--accent)"></i>
                </span>
                <span class="cat-icon__name">Все</span>
            </a>
            @foreach($mainCategories as $cat)
                @php $isActive = (int)$cat->id === (int)$category->id; @endphp
                <a href="{{ route('webapp.category.products', ['category' => $cat, 'chat_id' => request('chat_id')]) }}"
                   class="cat-icon {{ $isActive ? 'is-active' : '' }}">
                    <span class="cat-icon__img-wrap">
                        @if($cat->photo_url)
                            <img class="cat-icon__img" src="{{ asset('storage/' . $cat->photo_url) }}" alt="{{ $cat->name }}" loading="lazy">
                        @else
                            <span class="cat-icon__emoji">{{ $catEmojis[$cat->name] ?? '🍽️' }}</span>
                        @endif
                    </span>
                    <span class="cat-icon__name">{{ $cat->name }}</span>
                </a>
            @endforeach
            <button type="button" class="cat-icon" id="categoryTrigger" style="background:none;border:none;cursor:pointer;font-family:inherit">
                <span class="cat-icon__img-wrap" style="background:var(--bg-input)">
                    <i data-lucide="more-horizontal" style="width:20px;height:20px;color:var(--text-secondary)"></i>
                </span>
                <span class="cat-icon__name">Ещё</span>
            </button>
        </div>
    @endif

    {{-- Category modal --}}
    <div class="category-modal-overlay" id="categoryModalOverlay"></div>
    <div class="category-modal" id="categoryModal">
        <div class="category-modal__header">
            <span class="category-modal__title">Категории</span>
            <button type="button" class="category-modal__close" id="categoryModalClose" aria-label="Закрыть">×</button>
        </div>
        <div class="category-modal__search-wrap">
            <input type="text" class="category-modal__search" id="categoryModalSearch"
                   placeholder="Поиск категории..." autocomplete="off">
        </div>
        <div class="category-modal__list">
            <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}" class="category-modal__item">
                <span class="category-modal__item-icon">🛍</span>
                <span class="category-modal__item-name">{{ __('webapp.all_shop') }}</span>
                <span class="category-modal__item-arrow">›</span>
            </a>
            @foreach(($mainCategories ?? collect([$category])) as $cat)
                @include('webapp.partials._category-modal-item', [
                    'cat' => $cat,
                    'level' => 0,
                    'currentCategoryId' => $category->id,
                ])
            @endforeach
        </div>
    </div>

    {{-- FILTERS --}}
    @if(($attributes ?? collect())->isNotEmpty())
        <details class="filters-accordion">
            <summary>
                <span><i data-lucide="sliders-horizontal" style="width:16px;height:16px;margin-right:6px;vertical-align:-2px"></i>{{ __('webapp.filters') }}</span>
                <i data-lucide="chevron-down" style="width:14px;height:14px;color:var(--text-muted)"></i>
            </summary>
            <div class="filters-accordion__body">
                <form action="{{ url()->current() }}" method="get" class="filters">
                    <input type="hidden" name="query" value="{{ $query ?? request('query') }}">
                    @if(($categoryTree ?? collect())->isNotEmpty())
                        <div class="filters__group">
                            <div class="filters__title">{{ __('webapp.categories') }}</div>
                            <div class="filters__values filters__values--column">
                                @include('webapp.partials.category-filter-options', [
                                    'categories' => $categoryTree,
                                    'selectedCategoryId' => $selectedCategoryId ?? null,
                                    'level' => 0
                                ])
                            </div>
                        </div>
                    @endif
                    @foreach($attributes as $attribute)
                        <div class="filters__group">
                            <div class="filters__title">{{ $attribute->name }}</div>
                            <div class="filters__values">
                                @foreach($attribute->values as $value)
                                    <label>
                                        <input type="checkbox"
                                               name="attributes[{{ $attribute->id }}][]"
                                               value="{{ $value->value }}"
                                               @checked(in_array($value->value, $selectedAttributes[$attribute->id] ?? []))>
                                        <span>{{ $value->value }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <div class="filters__actions">
                        <button type="submit">{{ __('webapp.apply') }}</button>
                        <a href="{{ url()->current() }}">{{ __('webapp.reset') }}</a>
                    </div>
                </form>
            </div>
        </details>
    @endif

    {{-- SECTION TITLE --}}
    <div class="section-title">{{ $category->name }}</div>

    @if($products->count())
        <div class="products-grid">
            @foreach($products as $product)
                @include('webapp.partials._product-tile', ['product' => $product, 'promotion' => $promotion])
            @endforeach
        </div>

        {{-- PAGINATION --}}
        @if($products->hasPages())
            @php
                $current = $products->currentPage();
                $last = $products->lastPage();
                $window = 1;
                $pages = [1];
                for ($i = $current - $window; $i <= $current + $window; $i++) {
                    if ($i > 1 && $i < $last) $pages[] = $i;
                }
                if ($last > 1) $pages[] = $last;
                $pages = array_values(array_unique($pages));
                sort($pages);
            @endphp
            <div class="pagination-wrap">
                @if($products->onFirstPage())
                    <span class="disabled">&laquo;</span>
                @else
                    <a href="{{ $products->previousPageUrl() }}">&laquo;</a>
                @endif

                @php $prev = 0; @endphp
                @foreach($pages as $page)
                    @if($prev > 0 && $page - $prev > 1)
                        <span class="disabled">…</span>
                    @endif
                    @if($page == $current)
                        <span class="current">{{ $page }}</span>
                    @else
                        <a href="{{ $products->url($page) }}">{{ $page }}</a>
                    @endif
                    @php $prev = $page; @endphp
                @endforeach

                @if($products->hasMorePages())
                    <a href="{{ $products->nextPageUrl() }}">&raquo;</a>
                @else
                    <span class="disabled">&raquo;</span>
                @endif
            </div>
        @endif
    @else
        <div class="empty-state">
            <div class="empty-state__icon"><i data-lucide="package-open"></i></div>
            <div class="empty-state__text">{{ __('webapp.products_empty') }}</div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        const csrf = "{{ csrf_token() }}";

        // Category modal + subcategory expansion
        (function () {
            const trigger = document.getElementById('categoryTrigger');
            const modal = document.getElementById('categoryModal');
            const overlay = document.getElementById('categoryModalOverlay');
            const closeBtn = document.getElementById('categoryModalClose');
            if (!trigger || !modal) return;

            function open() {
                modal.classList.add('is-open');
                overlay.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }
            function close() {
                modal.classList.remove('is-open');
                overlay.classList.remove('is-open');
                document.body.style.overflow = '';
            }
            trigger.addEventListener('click', () => {
                modal.classList.contains('is-open') ? close() : open();
            });
            overlay.addEventListener('click', close);
            closeBtn.addEventListener('click', close);

            modal.addEventListener('click', function (e) {
                const btn = e.target.closest('.js-cat-expand');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                const id = btn.dataset.catId;
                const children = document.getElementById('cat-children-' + id);
                if (!children) return;
                const isOpen = children.style.display !== 'none';
                children.style.display = isOpen ? 'none' : 'block';
                btn.classList.toggle('is-expanded', !isOpen);
            });

            const search = document.getElementById('categoryModalSearch');
            if (search) {
                search.addEventListener('input', function () {
                    const q = this.value.trim().toLowerCase();
                    const items = modal.querySelectorAll('.category-modal__item');
                    if (!q) {
                        modal.querySelectorAll('.is-hidden').forEach(el => el.classList.remove('is-hidden'));
                        modal.querySelectorAll('.category-modal__children').forEach(el => el.style.display = 'none');
                        modal.querySelectorAll('.js-cat-expand').forEach(el => el.classList.remove('is-expanded'));
                        return;
                    }
                    modal.querySelectorAll('.category-modal__children').forEach(el => el.style.display = 'block');
                    modal.querySelectorAll('.js-cat-expand').forEach(el => el.classList.add('is-expanded'));
                    items.forEach(item => {
                        const name = item.querySelector('.category-modal__item-name')?.textContent.toLowerCase() || '';
                        const matches = name.includes(q);
                        const row = item.closest('.category-modal__row');
                        (row || item).classList.toggle('is-hidden', !matches);
                    });
                });
            }
        })();

        // Favorites
        function toggleFav(btn, productId) {
            fetch("/api/webapp/favorite/toggle", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                body: JSON.stringify({ chat_id: userId, product_id: productId })
            })
            .then(r => r.json())
            .then(data => {
                btn.classList.toggle('active', data.favorite);
                tg && tg.HapticFeedback && tg.HapticFeedback.impactOccurred(data.favorite ? "medium" : "light");
            });
        }

        if (userId) {
            fetch(`/api/webapp/favorite/list?chat_id=${userId}`)
                .then(r => r.json())
                .then(data => {
                    const favIds = (data.favorites || []).map(f => f.product_id);
                    document.querySelectorAll('.js-fav-btn').forEach(btn => {
                        if (favIds.includes(parseInt(btn.dataset.product))) btn.classList.add('active');
                    });
                });
        }

        function loadCartCount() {
            if (!userId) return;
            fetch(`/api/webapp/cart/count?chat_id=${userId}`)
                .then(r => r.json())
                .then(data => {
                    const badge = document.getElementById('bottom-cart-badge');
                    if (badge) {
                        badge.style.display = data.count > 0 ? 'block' : 'none';
                        badge.innerText = data.count;
                    }
                });
        }
        loadCartCount();
    </script>
@endsection
