@extends('webapp.layout')

@section('title', __('webapp.cart_title'))

@section('nav'){{-- Custom nav in cart panel --}}@endsection

@section('content')
    {{-- PAGE HEADER --}}
    <div class="page-header">
        <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}"
           class="page-header__back"><i data-lucide="arrow-left"></i></a>
        <div class="page-header__title">{{ __('webapp.cart_title') }}</div>
    </div>

    {{-- CART ITEMS --}}
    <div class="cart-page">
        @forelse($cart->items as $item)
            @php
                $itemPricing = $pricing['items'][$item->id] ?? null;
                $unitPrice = $itemPricing['final_unit_price'] ?? $item->product->price;
                $badge = null;
                if (($itemPricing['product_discount'] ?? 0) > 0) {
                    $badge = '-'.($itemPricing['product_discount']).'%';
                } elseif (($itemPricing['applied_type'] ?? null) === 'promo_percent') {
                    $badge = '-'.($pricing['promotion_percent'] ?? 0).'%';
                } elseif (($itemPricing['applied_type'] ?? null) === 'promo_one_plus_two') {
                    $badge = '1+2';
                }
            @endphp
            <div class="cart-item" data-id="{{ $item->id }}">
                <img class="cart-item__img"
                     src="{{ $item->product->images->first() ? asset('storage/' . $item->product->images->first()->url) : '/no-image.png' }}"
                     alt="">
                <div class="cart-item__info">
                    <div class="cart-item__category">{{ $item->product->category->name ?? '' }}</div>
                    <div class="cart-item__name">{{ $item->product->name }}</div>
                    @if($badge)
                        <span class="cart-item__badge">{{ $badge }}</span>
                    @endif
                    <div class="cart-item__bottom">
                        <span class="cart-item__price">
                            <span class="cart-item__qty-display">{{ $item->quantity }}</span>
                            &times;
                            <span class="cart-item__unit-price">{{ number_format($unitPrice, 0, '.', ' ') }}</span>
                            &#8381;
                        </span>
                        <div class="cart-item__controls">
                            <button class="cart-item__qty-btn cart-minus" data-id="{{ $item->id }}">
                                <i data-lucide="minus" style="width:14px;height:14px"></i>
                            </button>
                            <button class="cart-item__qty-btn cart-plus" data-id="{{ $item->id }}">
                                <i data-lucide="plus" style="width:14px;height:14px"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button class="cart-item__del cart-remove" data-id="{{ $item->id }}">
                    <i data-lucide="x" style="width:12px;height:12px"></i>
                </button>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state__icon"><i data-lucide="shopping-bag"></i></div>
                <div class="empty-state__text">{{ __('webapp.cart_empty') }}</div>
            </div>
        @endforelse
    </div>

    {{-- PAYMENT PANEL --}}
    <div class="cart-panel">
        {{-- PROMO CODE --}}
        <div class="promo-code-section" id="promoSection">
            @if($cart->promoCode)
                <div class="promo-code-applied" id="promoApplied">
                    <div class="promo-code-applied__info">
                        <i data-lucide="ticket" style="width:16px;height:16px;color:var(--accent)"></i>
                        <span class="promo-code-applied__code">{{ $cart->promoCode->code }}</span>
                        <span class="promo-code-applied__value">
                            &minus;{{ number_format($pricing['promo_code_discount'], 0, '.', ' ') }} &#8381;
                        </span>
                    </div>
                    <button type="button" class="promo-code-applied__remove" id="removePromo">
                        <i data-lucide="x" style="width:14px;height:14px"></i>
                    </button>
                </div>
                <div class="promo-code-form" id="promoForm" style="display:none">
                    <input type="text" id="promoInput" class="promo-code-form__input"
                           placeholder="{{ __('webapp.promo_code_placeholder') }}" autocomplete="off">
                    <button type="button" class="promo-code-form__btn" id="applyPromo">
                        {{ __('webapp.promo_apply') }}
                    </button>
                </div>
            @else
                <div class="promo-code-applied" id="promoApplied" style="display:none">
                    <div class="promo-code-applied__info">
                        <i data-lucide="ticket" style="width:16px;height:16px;color:var(--accent)"></i>
                        <span class="promo-code-applied__code"></span>
                        <span class="promo-code-applied__value"></span>
                    </div>
                    <button type="button" class="promo-code-applied__remove" id="removePromo">
                        <i data-lucide="x" style="width:14px;height:14px"></i>
                    </button>
                </div>
                <div class="promo-code-form" id="promoForm">
                    <input type="text" id="promoInput" class="promo-code-form__input"
                           placeholder="{{ __('webapp.promo_code_placeholder') }}" autocomplete="off">
                    <button type="button" class="promo-code-form__btn" id="applyPromo">
                        {{ __('webapp.promo_apply') }}
                    </button>
                </div>
            @endif
            <div class="promo-code-error" id="promoError" style="display:none"></div>
        </div>

        <div class="cart-panel__row">
            <span>{{ __('webapp.subtotal') }}:</span>
            <span class="subtotal-price">{{ number_format($pricing['subtotal'], 0, '.', ' ') }} &#8381;</span>
        </div>
        @if(($pricing['volume_tier_percent'] ?? 0) > 0)
        <div class="cart-panel__row cart-panel__row--discount">
            <span>{{ __('webapp.volume_discount') }} ({{ $pricing['volume_tier_percent'] }}%):</span>
            <span class="volume-discount-price">&minus;{{ number_format($pricing['volume_discount'], 0, '.', ' ') }} &#8381;</span>
        </div>
        @endif
        <div class="cart-panel__row cart-panel__row--discount">
            <span>{{ __('webapp.discount') }}:</span>
            <span class="discount-price">&minus;{{ number_format($pricing['discount_total'], 0, '.', ' ') }} &#8381;</span>
        </div>
        <div class="cart-panel__row cart-panel__row--total">
            <span>{{ __('webapp.total') }}:</span>
            <span class="total-price">{{ number_format($pricing['total'], 0, '.', ' ') }} &#8381;</span>
        </div>

        @php
            $minOrder = 1500;
            $cartTotal = (float) ($pricing['total'] ?? 0);
            $isEmpty = $cart->items->count() === 0;
            $isBelowMin = !$isEmpty && $cartTotal < $minOrder;
            $isDisabled = $isEmpty || $isBelowMin;
            if ($isEmpty) {
                $btnText = __('webapp.cart_empty');
            } elseif ($isBelowMin) {
                $btnText = __('webapp.min_order_hint', ['amount' => number_format($minOrder, 0, '.', ' ')]);
            } else {
                $btnText = __('webapp.place_order');
            }
        @endphp
        <a href="{{ route('webapp.checkout', ['chat_id' => request('chat_id')]) }}"
           class="cart-panel__btn" id="make-order"
           data-min-order="{{ $minOrder }}"
           style="{{ $isDisabled ? 'opacity:0.4;pointer-events:none;background:var(--text-muted);box-shadow:none;display:block' : 'display:block' }}">
            {{ $btnText }}
        </a>
    </div>
@endsection

@section('scripts')
    <script>
        const csrf = "{{ csrf_token() }}";
        const emptyText = "{{ __('webapp.cart_empty') }}";
        const placeOrderText = "{{ __('webapp.place_order') }}";
        const minOrderAmount = {{ $minOrder }};
        const minOrderFormatted = "{{ number_format($minOrder, 0, '.', ' ') }}";
        const minOrderHintTemplate = "{{ __('webapp.min_order_hint', ['amount' => ':amount']) }}";

        function showError(message) {
            const box = document.getElementById('alert-box');
            box.innerText = message;
            box.classList.add('show');
            tg.HapticFeedback.notificationOccurred("error");
            setTimeout(() => box.classList.remove('show'), 2000);
        }

        function parseAmount(v) {
            if (v === null || v === undefined) return 0;
            if (typeof v === 'number') return v;
            const n = Number(String(v).replace(/\s+/g, '').replace(',', '.'));
            return Number.isFinite(n) ? n : 0;
        }
        function formatAmount(v) {
            return Math.round(parseAmount(v)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }

        function disableBtn(btn, text) {
            btn.style.opacity = '0.4';
            btn.style.pointerEvents = 'none';
            btn.style.background = 'var(--text-muted)';
            btn.style.boxShadow = 'none';
            btn.innerText = text;
        }
        function enableBtn(btn, text) {
            btn.style.opacity = '';
            btn.style.pointerEvents = '';
            btn.style.background = '';
            btn.style.boxShadow = '';
            btn.innerText = text;
        }

        function applyTotals(data) {
            document.querySelectorAll('.subtotal-price').forEach(el => el.textContent = formatAmount(data.subtotal ?? 0) + ' \u20BD');
            document.querySelectorAll('.discount-price').forEach(el => el.textContent = formatAmount(data.discount_total ?? 0) + ' \u20BD');
            document.querySelectorAll('.total-price').forEach(el => el.textContent = formatAmount(data.total) + ' \u20BD');

            const btn = document.getElementById('make-order');
            if (!btn) return;

            const count = Number(data.count);
            const total = parseAmount(data.total ?? 0);

            if (!isNaN(count) && count === 0) {
                disableBtn(btn, emptyText);
            } else if (total < minOrderAmount) {
                const hint = minOrderHintTemplate.replace(':amount', minOrderFormatted);
                disableBtn(btn, hint);
            } else {
                enableBtn(btn, placeOrderText);
            }
        }

        function updateQty(itemId, delta) {
            fetch("/api/webapp/cart/update", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                body: JSON.stringify({ item_id: itemId, delta })
            })
            .then(r => {
                if (!r.ok) {
                    return r.json().catch(() => ({ success: false, message: 'Ошибка сервера' }));
                }
                return r.json();
            })
            .then(data => {
                if (!data.success) { showError(data.message ?? "{{ __('webapp.add_error') }}"); return; }
                const card = document.querySelector(`.cart-item[data-id="${itemId}"]`);
                if (data.quantity === 0) {
                    card.remove();
                } else {
                    const qtyEl = card.querySelector('.cart-item__qty-display');
                    const priceEl = card.querySelector('.cart-item__unit-price');
                    if (qtyEl) qtyEl.innerText = data.quantity;
                    if (priceEl && data.item_unit_price !== null) priceEl.innerText = formatAmount(data.item_unit_price);
                }
                applyTotals(data);
                tg.HapticFeedback.impactOccurred("light");
            })
            .catch(err => {
                console.error('updateQty error:', err);
                showError("{{ __('webapp.add_error') }}");
            });
        }

        document.querySelectorAll('.cart-remove').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = this.dataset.id;
                fetch("/api/webapp/cart/remove", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                    body: JSON.stringify({ item_id: itemId })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.error || data.success === false) { showError(data.error ?? data.message); return; }
                    document.querySelector(`.cart-item[data-id="${itemId}"]`).remove();
                    applyTotals(data);
                });
            });
        });

        document.querySelectorAll('.cart-plus').forEach(btn => {
            btn.addEventListener('click', () => updateQty(btn.dataset.id, +1));
        });
        document.querySelectorAll('.cart-minus').forEach(btn => {
            btn.addEventListener('click', () => updateQty(btn.dataset.id, -1));
        });

        // === PROMO CODE ===
        const promoForm = document.getElementById('promoForm');
        const promoApplied = document.getElementById('promoApplied');
        const promoInput = document.getElementById('promoInput');
        const applyPromoBtn = document.getElementById('applyPromo');
        const removePromoBtn = document.getElementById('removePromo');
        const promoError = document.getElementById('promoError');

        function showPromoError(msg) {
            promoError.textContent = msg;
            promoError.style.display = 'block';
            setTimeout(() => promoError.style.display = 'none', 3000);
        }

        applyPromoBtn?.addEventListener('click', function() {
            const code = promoInput.value.trim();
            if (!code) return;

            this.disabled = true;
            this.textContent = '...';

            fetch("/api/webapp/cart/apply-promo", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                body: JSON.stringify({ chat_id: userId, code: code })
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                applyPromoBtn.disabled = false;
                applyPromoBtn.textContent = "{{ __('webapp.promo_apply') }}";

                if (!ok || !data.success) {
                    showPromoError(data.message || "{{ __('webapp.promo_invalid') }}");
                    tg.HapticFeedback.notificationOccurred("error");
                    return;
                }

                // Show applied state
                promoForm.style.display = 'none';
                promoApplied.style.display = 'flex';
                promoApplied.querySelector('.promo-code-applied__code').textContent = data.promo_code;
                promoApplied.querySelector('.promo-code-applied__value').innerHTML =
                    '&minus;' + formatAmount(data.promo_discount) + ' \u20BD';
                promoError.style.display = 'none';

                // Re-init lucide for new icons
                lucide.createIcons();

                applyTotals(data);
                tg.HapticFeedback.notificationOccurred("success");
            })
            .catch(() => {
                applyPromoBtn.disabled = false;
                applyPromoBtn.textContent = "{{ __('webapp.promo_apply') }}";
                showPromoError("{{ __('webapp.promo_invalid') }}");
            });
        });

        promoInput?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyPromoBtn?.click();
            }
        });

        removePromoBtn?.addEventListener('click', function() {
            fetch("/api/webapp/cart/remove-promo", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                body: JSON.stringify({ chat_id: userId })
            })
            .then(r => r.json())
            .then(data => {
                promoApplied.style.display = 'none';
                promoForm.style.display = 'flex';
                promoInput.value = '';
                applyTotals(data);
                tg.HapticFeedback.impactOccurred("light");
            });
        });

        // No order modal - redirect to checkout page via link
    </script>
@endsection
