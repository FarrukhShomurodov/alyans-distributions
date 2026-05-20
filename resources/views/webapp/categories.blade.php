@extends('webapp.layout')

@section('head')
    <style>
        /* Random products mini-slider (carousel-style, 2 per slide) */
        .rec-slider {
            position: relative;
            overflow: hidden;
            margin: 0 14px 14px;
        }
        .rec-slider__track {
            display: flex;
            transition: transform 0.45s ease;
            will-change: transform;
        }
        .rec-slider__slide {
            min-width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 0 2px;
        }
        .rec-slider__nav {
            position: absolute;
            top: 45%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fff;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .rec-slider__nav--prev { left: 4px; }
        .rec-slider__nav--next { right: 4px; }
        .rec-slider__dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
        }
        .rec-slider__dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--text-muted);
            opacity: 0.35;
            cursor: pointer;
            transition: all 0.2s;
        }
        .rec-slider__dot.active {
            opacity: 1;
            background: var(--accent);
            width: 20px;
            border-radius: 4px;
        }
    </style>
@endsection

@section('content')
    {{-- TOP HEADER: brand bar with inline search --}}
    <header class="top-header">
        <button class="top-header__burger" id="openSidebar" aria-label="Меню"><i data-lucide="menu"></i></button>
        <form action="{{ route('webapp.products') }}" method="get" class="header-search" role="search">
            <span class="header-search__icon"><i data-lucide="search" style="width:18px;height:18px"></i></span>
            <input type="text" name="query" class="header-search__input"
                   placeholder="{{ __('webapp.search_placeholder') }}"
                   value="{{ request('query') }}"
                   autocomplete="off">
        </form>
    </header>

    {{-- CATEGORY PILLS (modal trigger button + active filter) --}}
    @php
        $pillCategories = $categories;
        if ($categories->count() === 1) {
            $root = $categories->first();
            if ($root->relationLoaded('childrenRecursive') && $root->childrenRecursive->count() > 0) {
                $pillCategories = $root->childrenRecursive;
            }
        }
    @endphp

    {{-- HORIZONTAL CATEGORY ICONS (EVOS-style) --}}
    @if($pillCategories->count())
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
            <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}" class="cat-icon cat-icon--all is-active">
                <span class="cat-icon__img-wrap">
                    <i data-lucide="layout-grid" style="width:22px;height:22px;color:var(--accent)"></i>
                </span>
                <span class="cat-icon__name">Все</span>
            </a>
            @foreach($pillCategories as $cat)
                <a href="{{ route('webapp.category.products', ['category' => $cat, 'chat_id' => request('chat_id')]) }}"
                   class="cat-icon">
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

    {{-- Category modal (full list with subcategories) --}}
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
            <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}" class="category-modal__item active">
                <span class="category-modal__item-icon">🛍</span>
                <span class="category-modal__item-name">{{ __('webapp.all_shop') }}</span>
                <span class="category-modal__item-arrow">›</span>
            </a>
            @foreach($pillCategories as $cat)
                @include('webapp.partials._category-modal-item', [
                    'cat' => $cat,
                    'level' => 0,
                    'currentCategoryId' => null,
                ])
            @endforeach
        </div>
    </div>

    {{-- BANNER CAROUSEL --}}
    @if(($carouselItems ?? collect())->count())
        <div class="slider" data-slider>
            <button class="slider__btn slider__btn--prev" type="button" aria-label="Назад">&#8249;</button>
            <button class="slider__btn slider__btn--next" type="button" aria-label="Вперёд">&#8250;</button>
            <div class="slider__track">
                @foreach($carouselItems as $item)
                    <a class="slider__slide"
                       href="{{ $item->category ? route('webapp.category.products', $item->category) : route('webapp') }}">
                        <img class="slider__image" loading="lazy"
                             src="{{ asset('storage/' . $item->image_path) }}" alt="slide">
                        @if($item->title)
                            <span class="slider__caption">{{ $item->title }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="slider__dots"></div>
        </div>
    @endif

    {{-- RECOMMENDED MINI-CAROUSEL (2 cards per slide) --}}
    @if(($sliderProducts ?? collect())->count())
        @php $recSlides = $sliderProducts->chunk(2); @endphp
        <div class="section-title">{{ __('webapp.recommended') }}</div>
        <div class="rec-slider" data-rec-slider>
            <button class="rec-slider__nav rec-slider__nav--prev" type="button" aria-label="Назад"><i data-lucide="chevron-left"></i></button>
            <button class="rec-slider__nav rec-slider__nav--next" type="button" aria-label="Вперёд"><i data-lucide="chevron-right"></i></button>
            <div class="rec-slider__track">
                @foreach($recSlides as $chunk)
                    <div class="rec-slider__slide">
                        @foreach($chunk as $product)
                            @php
                                $pd = (int) ($product->discount_percent ?? 0);
                                $pt = $promotion?->active_type ?? null;
                                $pp = (int) ($promotion?->discount_percent ?? 0);
                                $fp = $product->price;
                                $badge = null;
                                if ($pd > 0) { $badge = '-'.$pd.'%'; $fp = $product->price * (100-$pd)/100; }
                                elseif ($pt === \App\Models\PromotionSetting::TYPE_PERCENT && $pp > 0) { $badge = '-'.$pp.'%'; $fp = $product->price * (100-$pp)/100; }
                                elseif ($pt === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO) { $badge = '1+2'; }
                            @endphp
                            <a href="{{ route('webapp.product.show', $product->id) }}" class="product-tile">
                                @if($badge)
                                    <span class="product-tile__discount">{{ $badge }}</span>
                                @endif
                                <button class="product-tile__fav js-fav-btn" data-product="{{ $product->id }}"
                                        onclick="event.preventDefault(); event.stopPropagation(); toggleFav(this, {{ $product->id }})">
                                    <i data-lucide="heart"></i>
                                </button>
                                <img class="product-tile__img" loading="lazy"
                                     src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->url) : '/no-image.png' }}"
                                     alt="{{ $product->name }}">
                                <div class="product-tile__info">
                                    <div class="product-tile__name">{{ $product->name }}</div>
                                    <div class="product-tile__bottom">
                                        <div class="product-tile__price">
                                            {{ number_format($fp, 0, '.', ' ') }} сум
                                            @if($fp < $product->price)
                                                <span class="product-tile__price-old">{{ number_format($product->price, 0, '.', ' ') }}</span>
                                            @endif
                                        </div>
                                        <button class="product-tile__cart" onclick="event.preventDefault(); event.stopPropagation(); addToCartFromTile(this, {{ $product->id }})">
                                            <i data-lucide="shopping-cart"></i>
                                        </button>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="rec-slider__dots"></div>
        </div>
    @endif

    {{-- CATALOG --}}
    <div class="section-title">
        {{ __('webapp.all_products') }}
        @if(($products ?? collect())->count())
            <a href="{{ route('webapp.products') }}" class="section-title__link">Все →</a>
        @endif
    </div>

    @if(($products ?? collect())->count())
        <div class="products-grid">
            @foreach($products as $product)
                @php
                    $pd = (int) ($product->discount_percent ?? 0);
                    $pt = $promotion?->active_type ?? null;
                    $pp = (int) ($promotion?->discount_percent ?? 0);
                    $fp = $product->price;
                    $badge = null;
                    if ($pd > 0) { $badge = '-'.$pd.'%'; $fp = $product->price * (100-$pd)/100; }
                    elseif ($pt === \App\Models\PromotionSetting::TYPE_PERCENT && $pp > 0) { $badge = '-'.$pp.'%'; $fp = $product->price * (100-$pp)/100; }
                    elseif ($pt === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO) { $badge = '1+2'; }
                @endphp
                <a href="{{ route('webapp.product.show', $product->id) }}" class="product-tile">
                    @if($badge)
                        <span class="product-tile__discount">{{ $badge }}</span>
                    @endif
                    <button class="product-tile__fav js-fav-btn" data-product="{{ $product->id }}"
                            onclick="event.preventDefault(); event.stopPropagation(); toggleFav(this, {{ $product->id }})">
                        <i data-lucide="heart"></i>
                    </button>
                    <img class="product-tile__img" loading="lazy"
                         src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->url) : '/no-image.png' }}"
                         alt="{{ $product->name }}">
                    <div class="product-tile__info">
                        <div class="product-tile__name">{{ $product->name }}</div>
                        <div class="product-tile__bottom">
                            <div class="product-tile__price">
                                {{ number_format($fp, 0, '.', ' ') }} сум
                                @if($fp < $product->price)
                                    <span class="product-tile__price-old">{{ number_format($product->price, 0, '.', ' ') }}</span>
                                @endif
                            </div>
                            <button class="product-tile__cart" onclick="event.preventDefault(); event.stopPropagation(); addToCartFromTile(this, {{ $product->id }})">
                                <i data-lucide="shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                </a>
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

        // Category modal open/close + subcategory expand
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
                        const target = row || item;
                        target.classList.toggle('is-hidden', !matches);
                    });
                });
            }
        })();

        // Banner slider
        const slider = document.querySelector('[data-slider]');
        if (slider) {
            const track = slider.querySelector('.slider__track');
            const slides = Array.from(slider.querySelectorAll('.slider__slide'));
            const dotsWrap = slider.querySelector('.slider__dots');
            const prevBtn = slider.querySelector('.slider__btn--prev');
            const nextBtn = slider.querySelector('.slider__btn--next');
            let index = 0;

            slides.forEach((_, i) => {
                const dot = document.createElement('span');
                dot.className = 'slider__dot' + (i === 0 ? ' active' : '');
                dot.addEventListener('click', () => { index = i; updateSlider(); });
                dotsWrap.appendChild(dot);
            });

            const updateSlider = () => {
                track.style.transform = `translateX(-${index * 100}%)`;
                dotsWrap.querySelectorAll('.slider__dot').forEach((d, i) => {
                    d.classList.toggle('active', i === index);
                });
            };

            prevBtn?.addEventListener('click', () => { index = (index - 1 + slides.length) % slides.length; updateSlider(); });
            nextBtn?.addEventListener('click', () => { index = (index + 1) % slides.length; updateSlider(); });
            if (slides.length > 1) {
                setInterval(() => { index = (index + 1) % slides.length; updateSlider(); }, 4500);
            }
        }

        // Favorites toggle
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

        // Load fav states
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

        // Recommended mini-slider
        const recSlider = document.querySelector('[data-rec-slider]');
        if (recSlider) {
            const recTrack = recSlider.querySelector('.rec-slider__track');
            const recSlides = Array.from(recSlider.querySelectorAll('.rec-slider__slide'));
            const recDotsWrap = recSlider.querySelector('.rec-slider__dots');
            const recPrev = recSlider.querySelector('.rec-slider__nav--prev');
            const recNext = recSlider.querySelector('.rec-slider__nav--next');
            let recIdx = 0;

            recSlides.forEach((_, i) => {
                const dot = document.createElement('span');
                dot.className = 'rec-slider__dot' + (i === 0 ? ' active' : '');
                dot.addEventListener('click', () => { recIdx = i; updateRec(); });
                recDotsWrap.appendChild(dot);
            });

            const updateRec = () => {
                recTrack.style.transform = `translateX(-${recIdx * 100}%)`;
                recDotsWrap.querySelectorAll('.rec-slider__dot').forEach((d, i) => {
                    d.classList.toggle('active', i === recIdx);
                });
            };

            recPrev?.addEventListener('click', () => { recIdx = (recIdx - 1 + recSlides.length) % recSlides.length; updateRec(); });
            recNext?.addEventListener('click', () => { recIdx = (recIdx + 1) % recSlides.length; updateRec(); });

            if (recSlides.length > 1) {
                setInterval(() => { recIdx = (recIdx + 1) % recSlides.length; updateRec(); }, 5500);
            }

            let recStartX = 0;
            recSlider.addEventListener('touchstart', e => recStartX = e.touches[0].clientX);
            recSlider.addEventListener('touchend', e => {
                const diff = recStartX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) {
                    recIdx = diff > 0
                        ? Math.min(recIdx + 1, recSlides.length - 1)
                        : Math.max(recIdx - 1, 0);
                    updateRec();
                }
            });
        }

        // Cart badge
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
