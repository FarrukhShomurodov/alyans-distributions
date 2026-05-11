@extends('webapp.layout')

@section('nav'){{-- Hide bottom nav on product page --}}@endsection

@section('content')
    @php
        $productDiscount = (int) ($product->discount_percent ?? 0);
        $promoType = $promotion?->active_type ?? null;
        $promoPercent = (int) ($promotion?->discount_percent ?? 0);
        $basePrice = (float) $product->price;
        $finalPrice = $basePrice;
        $discountBadge = null;

        if ($productDiscount > 0) {
            $discountBadge = '-'.$productDiscount.'%';
            $finalPrice = $basePrice * (100 - $productDiscount) / 100;
        } elseif ($promoType === \App\Models\PromotionSetting::TYPE_PERCENT && $promoPercent > 0) {
            $discountBadge = '-'.$promoPercent.'%';
            $finalPrice = $basePrice * (100 - $promoPercent) / 100;
        } elseif ($promoType === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO) {
            $discountBadge = '1+2';
        }

        $attributesGrouped = $product->attributes->groupBy('name');
    @endphp

    <div class="product-detail">
        {{-- IMAGE SECTION --}}
        <div class="product-detail__image-wrap">
            @php
                $productBackHref = route('webapp', ['chat_id' => request('chat_id')]);
                if ($product->category_id) {
                    $productBackHref = route('webapp.category.products', [
                        'category' => $product->category_id,
                        'chat_id' => request('chat_id'),
                    ]);
                }
            @endphp
            <a href="{{ $productBackHref }}"
               class="product-detail__back"><i data-lucide="arrow-left"></i></a>

            <div class="product-detail__actions">
                <button class="product-detail__action-btn" id="fav-btn" data-id="{{ $product->id }}">
                    <i data-lucide="heart"></i>
                </button>
                <button class="product-detail__action-btn" id="share-btn">
                    <i data-lucide="share-2"></i>
                </button>
            </div>

            @if($product->images->count() > 1)
                <div class="product-detail__image-slider" data-product-slider>
                    <div class="product-detail__slider-track">
                        @foreach($product->images as $image)
                            <div class="product-detail__slider-slide">
                                <img src="{{ asset('storage/' . $image->url) }}" alt="{{ $product->name }}">
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="product-detail__slider-dots">
                    @foreach($product->images as $i => $image)
                        <span class="product-detail__slider-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                    @endforeach
                </div>
            @elseif($product->images->count() === 1)
                <img class="product-detail__image" src="{{ asset('storage/' . $product->images->first()->url) }}"
                     alt="{{ $product->name }}">
            @else
                <img class="product-detail__image" src="/no-image.png" alt="No image">
            @endif
        </div>

        {{-- BODY --}}
        <div class="product-detail__body">
            <div class="product-detail__name">{{ $product->name }}</div>

            <div class="product-detail__price-row">
                <span class="product-detail__price">{{ number_format($finalPrice, 0, '.', ' ') }} &#8381;</span>
                @if($finalPrice < $basePrice)
                    <span class="product-detail__price-old">{{ number_format($basePrice, 0, '.', ' ') }} &#8381;</span>
                @endif
                @if($discountBadge)
                    <span class="product-detail__discount-badge">{{ $discountBadge }}</span>
                @endif
            </div>

            <div class="product-detail__meta">
                @if($product->category)
                    <div><strong>{{ __('webapp.category') }}:</strong> {{ $product->category->name }}</div>
                @endif
                @if($product->stock)
                    <div><strong>{{ __('webapp.in_stock') }}:</strong> {{ $product->stock->quantity }} {{ __('webapp.pcs') }}</div>
                @endif
                @if($attributesGrouped->isNotEmpty())
                    @foreach($attributesGrouped as $attributeName => $items)
                        <div>
                            <strong>{{ $attributeName }}:</strong>
                            {{ $items->pluck('pivot.value')->unique()->implode(', ') }}
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- ADD TO CART BAR --}}
    <div class="product-detail__cart-bar">
        <button class="product-detail__add-btn" id="add-to-cart-btn">
            <i data-lucide="shopping-bag" style="width:18px;height:18px;margin-right:6px;vertical-align:-3px"></i>{{ __('webapp.add_to_cart') }}
        </button>
        <div class="product-detail__qty-controls" id="qty-controls" style="display:none">
            <button class="product-detail__qty-btn qty-minus"><i data-lucide="minus"></i></button>
            <span class="product-detail__qty-value" id="qty-value">1</span>
            <button class="product-detail__qty-btn qty-plus"><i data-lucide="plus"></i></button>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const csrf = "{{ csrf_token() }}";
        const productId = {{ $product->id }};
        const favBtn = document.getElementById('fav-btn');
        const shareBtn = document.getElementById('share-btn');

        function showError(message) {
            const box = document.getElementById('alert-box');
            box.innerText = message;
            box.classList.add('show');
            tg.HapticFeedback.notificationOccurred("error");
            setTimeout(() => box.classList.remove('show'), 2000);
        }

        // FAVORITE
        if (userId) {
            fetch(`/api/webapp/favorite/check?chat_id=${userId}&product_id=${productId}`)
                .then(r => r.json())
                .then(data => favBtn.classList.toggle('fav-active', data.favorite));
        }

        favBtn.addEventListener('click', function() {
            fetch("/api/webapp/favorite/toggle", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                body: JSON.stringify({ chat_id: userId, product_id: productId })
            })
            .then(r => r.json())
            .then(data => {
                favBtn.classList.toggle('fav-active', data.favorite);
                tg.HapticFeedback.impactOccurred(data.favorite ? "medium" : "light");
            });
        });

        // SHARE — открывает нативное окно «Переслать...» в Telegram
        // При открытии ссылки получатель получит карточку товара с фото от бота
        shareBtn.addEventListener('click', function() {
            tg.HapticFeedback.impactOccurred("light");

            const botUsername = "{{ env('TELEGRAM_BOT_USERNAME', 'alyans_distributions_bot') }}";
            const deepLink = `https://t.me/${botUsername}?start=product_${productId}`;
            const shareLink = `https://t.me/share/url?url=${encodeURIComponent(deepLink)}`;

            tg.openTelegramLink(shareLink);
        });

        // IMAGE SLIDER
        const productSlider = document.querySelector('[data-product-slider]');
        if (productSlider) {
            const track = productSlider.querySelector('.product-detail__slider-track');
            const slides = Array.from(productSlider.querySelectorAll('.product-detail__slider-slide'));
            const dots = document.querySelectorAll('.product-detail__slider-dot');
            let index = 0;

            const updateSlider = () => {
                track.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach((d, i) => d.classList.toggle('active', i === index));
            };

            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    index = parseInt(dot.dataset.index);
                    updateSlider();
                });
            });

            // Swipe support
            let startX = 0;
            productSlider.addEventListener('touchstart', e => startX = e.touches[0].clientX);
            productSlider.addEventListener('touchend', e => {
                const diff = startX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) {
                    index = diff > 0
                        ? Math.min(index + 1, slides.length - 1)
                        : Math.max(index - 1, 0);
                    updateSlider();
                }
            });
        }

        // CART
        const addBtn = document.getElementById('add-to-cart-btn');
        const qtyControls = document.getElementById('qty-controls');
        const qtyValue = document.getElementById('qty-value');

        // Check if already in cart
        if (userId) {
            fetch(`/api/webapp/cart/items?chat_id=${userId}`)
                .then(r => r.json())
                .then(data => {
                    const found = data.items.find(i => i.product_id == productId);
                    if (found) {
                        addBtn.style.display = 'none';
                        qtyControls.dataset.itemId = found.item_id;
                        qtyValue.innerText = found.qty;
                        qtyControls.style.display = 'flex';
                    }
                });
        }

        addBtn.addEventListener('click', function() {
            fetch("/api/webapp/cart/add", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                body: JSON.stringify({ product_id: productId, chat_id: userId })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showError(data.message ?? "{{ __('webapp.add_error') }}"); return; }
                addBtn.style.display = 'none';
                qtyControls.dataset.itemId = data.item_id;
                qtyValue.innerText = 1;
                qtyControls.style.display = 'flex';
                tg.HapticFeedback.notificationOccurred("success");
            });
        });

        function updateQty(delta) {
            const itemId = qtyControls.dataset.itemId;
            fetch("/api/webapp/cart/update", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                body: JSON.stringify({ item_id: itemId, delta: delta })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showError(data.message ?? "{{ __('webapp.add_error') }}"); return; }
                if (data.quantity <= 0) {
                    qtyControls.style.display = 'none';
                    addBtn.style.display = 'block';
                } else {
                    qtyValue.innerText = data.quantity;
                }
            });
        }

        document.querySelector('.qty-plus').addEventListener('click', () => updateQty(+1));
        document.querySelector('.qty-minus').addEventListener('click', () => updateQty(-1));
    </script>
@endsection
