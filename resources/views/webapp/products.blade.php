@extends('webapp.layout')

@section('content')
    {{-- TOP HEADER --}}
    <header class="top-header">
        <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}" class="top-header__burger" aria-label="Назад"><i data-lucide="arrow-left"></i></a>
        <form action="{{ route('webapp.products') }}" method="get" class="header-search" role="search">
            <span class="header-search__icon"><i data-lucide="search" style="width:18px;height:18px"></i></span>
            <input type="text" name="query" class="header-search__input"
                   placeholder="{{ __('webapp.search_placeholder') }}"
                   value="{{ $query ?? request('query') }}"
                   autocomplete="off"
                   autofocus>
        </form>
    </header>

    {{-- FILTERS --}}
    @if($attributes->isNotEmpty())
        <details class="filters-accordion">
            <summary>
                <span><i data-lucide="sliders-horizontal" style="width:16px;height:16px;margin-right:6px;vertical-align:-2px"></i>{{ __('webapp.filters') }}</span>
                <i data-lucide="chevron-down" style="width:14px;height:14px;color:var(--text-muted)"></i>
            </summary>
            <div class="filters-accordion__body">
                <form action="{{ route('webapp.products') }}" method="get" class="filters">
                    <input type="hidden" name="query" value="{{ $query ?? request('query') }}">
                    @if(($categoriesTree ?? collect())->isNotEmpty())
                        <div class="filters__group">
                            <div class="filters__title">{{ __('webapp.categories') }}</div>
                            <div class="filters__values filters__values--column">
                                <label>
                                    <input type="radio" name="category_id" value="" @checked(empty($selectedCategoryId))>
                                    <span>{{ __('webapp.all_categories') }}</span>
                                </label>
                                @include('webapp.partials.category-filter-options', [
                                    'categories' => $categoriesTree,
                                    'selectedCategoryId' => $selectedCategoryId,
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
                        <a href="{{ route('webapp.products') }}">{{ __('webapp.reset') }}</a>
                    </div>
                </form>
            </div>
        </details>
    @endif

    {{-- SECTION TITLE --}}
    <div class="section-title">{{ __('webapp.all_products') }}</div>

    {{-- PRODUCTS GRID --}}
    <div class="products-grid">
        @foreach($products as $product)
            @php
                $productDiscount = (int) ($product->discount_percent ?? 0);
                $promoType = $promotion?->active_type ?? null;
                $promoPercent = (int) ($promotion?->discount_percent ?? 0);
                $discountBadge = null;
                $finalPrice = $product->price;
                if ($productDiscount > 0) {
                    $discountBadge = '-'.$productDiscount.'%';
                    $finalPrice = $product->price * (100 - $productDiscount) / 100;
                } elseif ($promoType === \App\Models\PromotionSetting::TYPE_PERCENT && $promoPercent > 0) {
                    $discountBadge = '-'.$promoPercent.'%';
                    $finalPrice = $product->price * (100 - $promoPercent) / 100;
                } elseif ($promoType === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO) {
                    $discountBadge = '1+2';
                }
            @endphp
            <a href="{{ route('webapp.product.show', $product->id) }}" class="product-tile">
                @if($discountBadge)
                    <span class="product-tile__discount">{{ $discountBadge }}</span>
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
                            {{ number_format($finalPrice, 0, '.', ' ') }} &#8381;
                            @if($finalPrice < $product->price)
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
            $pages = [];
            $pages[] = 1;
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

    @if($products->isEmpty())
        <div class="empty-state">
            <div class="empty-state__icon"><i data-lucide="search-x"></i></div>
            <div class="empty-state__text">{{ __('webapp.products_empty') }}</div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        const csrf = "{{ csrf_token() }}";

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
