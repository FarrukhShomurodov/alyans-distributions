@extends('webapp.layout')

@section('title', __('webapp.checkout_title'))
@section('nav'){{-- Hide nav --}}@endsection

@section('content')
    @php
        $tgUser = isset($user) && $user ? $user : null;
    @endphp

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <a href="{{ route('webapp.cart', ['chat_id' => request('chat_id')]) }}"
           class="page-header__back"><i data-lucide="arrow-left"></i></a>
        <div class="page-header__title">{{ __('webapp.checkout_title') }}</div>
    </div>

    {{-- STEP INDICATOR --}}
    <div class="checkout-steps" id="stepsIndicator">
        <div class="checkout-step active" data-step="1">
            <span class="checkout-step__num">1</span>
            <span>{{ __('webapp.step_contact') }}</span>
        </div>
        <div class="checkout-step__line"></div>
        <div class="checkout-step" data-step="2">
            <span class="checkout-step__num">2</span>
            <span>{{ __('webapp.step_review') }}</span>
        </div>
    </div>

    <div class="checkout-page">

        {{-- ============ STEP 1: CONTACT INFO ============ --}}
        <div class="checkout-panel active" id="step1">
            <div class="checkout-panel__title">{{ __('webapp.step_contact') }}</div>

            <div class="form-group">
                <label class="form-group__label">{{ __('webapp.last_name') }} <span class="required">*</span></label>
                <input type="text" class="form-group__input" id="inp_last_name"
                       value="{{ $user->saved_last_name ?? '' }}"
                       placeholder="{{ __('webapp.last_name') }}" required>
                <div class="form-group__error">{{ __('webapp.field_required') }}</div>
            </div>

            <div class="form-group">
                <label class="form-group__label">{{ __('webapp.first_name') }} <span class="required">*</span></label>
                <input type="text" class="form-group__input" id="inp_first_name"
                       value="{{ $tgUser->first_name ?? '' }}"
                       placeholder="{{ __('webapp.first_name') }}" required>
                <div class="form-group__error">{{ __('webapp.field_required') }}</div>
            </div>

            <div class="form-group">
                <label class="form-group__label">{{ __('webapp.patronymic') }}</label>
                <input type="text" class="form-group__input" id="inp_patronymic"
                       value="{{ $user->saved_patronymic ?? '' }}"
                       placeholder="{{ __('webapp.patronymic') }}">
            </div>

            <div class="form-group">
                <label class="form-group__label">{{ __('webapp.phone_number') }} <span class="required">*</span></label>
                <input type="tel" class="form-group__input" id="inp_phone"
                       value="{{ $user->phone ?? '' }}"
                       placeholder="+998 __ ___ __ __" required>
                <div class="form-group__error">{{ __('webapp.invalid_phone') }}</div>
            </div>

            <div class="form-group">
                <label class="form-group__label">{{ __('webapp.email_address') }}</label>
                <input type="email" class="form-group__input" id="inp_email"
                       value="{{ $user->saved_email ?? '' }}"
                       placeholder="email@example.com">
                <div class="form-group__error">{{ __('webapp.invalid_email') }}</div>
            </div>

            <div class="form-group">
                <label class="form-group__label">{{ __('webapp.order_comment') }}</label>
                <textarea class="form-group__input" id="inp_comment"
                          placeholder="{{ __('webapp.order_comment') }}" rows="3"></textarea>
            </div>
        </div>

        {{-- ============ STEP 2: REVIEW & CONFIRM ============ --}}
        <div class="checkout-panel" id="step2">
            <div class="checkout-panel__title">{{ __('webapp.review_title') }}</div>

            <div class="review-section">
                <div class="review-section__title">{{ __('webapp.review_contact') }}</div>
                <div class="review-section__row">
                    <span class="review-section__label">ФИО</span>
                    <span class="review-section__value" id="rv_fio">—</span>
                </div>
                <div class="review-section__row">
                    <span class="review-section__label">{{ __('webapp.phone_number') }}</span>
                    <span class="review-section__value" id="rv_phone">—</span>
                </div>
                <div class="review-section__row" id="rv_email_row" style="display:none">
                    <span class="review-section__label">{{ __('webapp.email_address') }}</span>
                    <span class="review-section__value" id="rv_email">—</span>
                </div>
            </div>

            <div class="review-section">
                <div class="review-section__title">{{ __('webapp.review_items') }}</div>
                @foreach($cart->items as $item)
                    @php
                        $ip = $pricing['items'][$item->id] ?? null;
                        $up = $ip['final_unit_price'] ?? $item->product->price;
                    @endphp
                    <div class="review-item">
                        <img class="review-item__img"
                             src="{{ $item->product->images->first() ? asset('storage/' . $item->product->images->first()->url) : '/no-image.png' }}" alt="">
                        <div class="review-item__name">{{ $item->product->name }}</div>
                        <div class="review-item__qty">×{{ $item->quantity }}</div>
                        <div class="review-item__price">{{ number_format($up * $item->quantity, 0, '.', ' ') }} сум</div>
                    </div>
                @endforeach
            </div>

            <div class="review-section" id="rv_comment_section" style="display:none">
                <div class="review-section__title">{{ __('webapp.review_comment') }}</div>
                <p style="font-size:13px;color:var(--text-secondary)" id="rv_comment_text"></p>
            </div>

            @if($cart->promoCode)
                <div class="review-section">
                    <div class="review-section__title">{{ __('webapp.review_promo') }}</div>
                    <div class="review-section__row">
                        <span class="review-section__label">{{ $cart->promoCode->code }}</span>
                        <span class="review-section__value" style="color:var(--success)">
                            −{{ number_format($pricing['promo_code_discount'], 0, '.', ' ') }} сум
                        </span>
                    </div>
                </div>
            @endif

            <div class="review-totals">
                <div class="review-totals__row">
                    <span>{{ __('webapp.items_total') }}</span>
                    <span>{{ number_format($pricing['subtotal'], 0, '.', ' ') }} сум</span>
                </div>
                @if(($pricing['volume_tier_percent'] ?? 0) > 0)
                <div class="review-totals__row review-totals__row--discount">
                    <span>{{ __('webapp.volume_discount') }} ({{ $pricing['volume_tier_percent'] }}%)</span>
                    <span>−{{ number_format($pricing['volume_discount'], 0, '.', ' ') }} сум</span>
                </div>
                @endif
                @if($pricing['discount_total'] > 0)
                <div class="review-totals__row review-totals__row--discount">
                    <span>{{ __('webapp.discount') }}</span>
                    <span>−{{ number_format($pricing['discount_total'], 0, '.', ' ') }} сум</span>
                </div>
                @endif
                <div class="review-totals__row review-totals__row--total">
                    <span>{{ __('webapp.total') }}</span>
                    <span id="rv_grand_total">{{ number_format($pricing['total'], 0, '.', ' ') }} сум</span>
                </div>
            </div>
        </div>

        {{-- ============ STEP 3: SUCCESS ============ --}}
        <div class="checkout-panel" id="step3">
            <div class="order-success">
                <div class="order-success__icon">🎉</div>
                <div class="order-success__title">{{ __('webapp.order_complete_title') }}</div>
                <div class="order-success__text">{{ __('webapp.order_complete_text') }}</div>
                <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}" class="order-success__btn">
                    {{ __('webapp.continue_shopping') }}
                </a>
            </div>
        </div>
    </div>

    {{-- BOTTOM BAR --}}
    <div class="checkout-bar" id="checkoutBar">
        <button class="checkout-bar__back" id="btnBack" style="display:none" onclick="prevStep()">
            <i data-lucide="arrow-left"></i>
        </button>
        <button class="checkout-bar__next" id="btnNext" onclick="nextStep()">
            {{ __('webapp.next_step') }}
        </button>
    </div>
@endsection

@section('scripts')
<script>
    const csrf = "{{ csrf_token() }}";
    let currentStep = 1;
    const TOTAL_STEPS = 2;

    function showStep(n) {
        currentStep = n;
        document.querySelectorAll('.checkout-panel').forEach(p => p.classList.remove('active'));
        const panel = document.getElementById('step' + n);
        if (panel) panel.classList.add('active');

        document.querySelectorAll('.checkout-step').forEach(s => {
            const step = parseInt(s.dataset.step);
            s.classList.remove('active', 'done');
            if (step === n) s.classList.add('active');
            else if (step < n) s.classList.add('done');
        });
        document.querySelectorAll('.checkout-step__line').forEach((l, i) => {
            l.classList.toggle('done', i + 1 < n);
        });

        const btnBack = document.getElementById('btnBack');
        const btnNext = document.getElementById('btnNext');
        const checkoutBar = document.getElementById('checkoutBar');

        if (n >= 3) {
            checkoutBar.style.display = 'none';
            return;
        }
        checkoutBar.style.display = '';
        btnBack.style.display = n === 1 ? 'none' : '';
        btnNext.innerText = n === TOTAL_STEPS
            ? "{{ __('webapp.place_order') }}"
            : "{{ __('webapp.next_step') }}";
    }

    function prevStep() {
        if (currentStep > 1) showStep(currentStep - 1);
    }

    function showError(message) {
        const box = document.getElementById('alert-box');
        if (!box) { alert(message); return; }
        box.innerText = message;
        box.classList.add('show');
        if (window.tg && tg.HapticFeedback) tg.HapticFeedback.notificationOccurred('error');
        setTimeout(() => box.classList.remove('show'), 2500);
    }

    function validateContact() {
        const fields = ['inp_first_name', 'inp_last_name', 'inp_phone'];
        let ok = true;
        for (const id of fields) {
            const el = document.getElementById(id);
            if (!el.value.trim()) {
                el.classList.add('error');
                ok = false;
            } else {
                el.classList.remove('error');
            }
        }
        const phone = document.getElementById('inp_phone').value.replace(/\D/g, '');
        if (phone.length < 9) {
            document.getElementById('inp_phone').classList.add('error');
            ok = false;
        }
        const email = document.getElementById('inp_email').value.trim();
        if (email && !/^[^@]+@[^@]+\.[^@]+$/.test(email)) {
            document.getElementById('inp_email').classList.add('error');
            ok = false;
        }
        if (!ok) showError("{{ __('webapp.field_required') }}");
        return ok;
    }

    function fillReview() {
        const ln = document.getElementById('inp_last_name').value.trim();
        const fn = document.getElementById('inp_first_name').value.trim();
        const pt = document.getElementById('inp_patronymic').value.trim();
        const ph = document.getElementById('inp_phone').value.trim();
        const em = document.getElementById('inp_email').value.trim();
        const cm = document.getElementById('inp_comment').value.trim();

        document.getElementById('rv_fio').textContent = [ln, fn, pt].filter(Boolean).join(' ') || '—';
        document.getElementById('rv_phone').textContent = ph || '—';

        const emailRow = document.getElementById('rv_email_row');
        if (em) {
            document.getElementById('rv_email').textContent = em;
            emailRow.style.display = '';
        } else {
            emailRow.style.display = 'none';
        }

        const cmSec = document.getElementById('rv_comment_section');
        if (cm) {
            document.getElementById('rv_comment_text').textContent = cm;
            cmSec.style.display = '';
        } else {
            cmSec.style.display = 'none';
        }
    }

    function nextStep() {
        if (currentStep === 1) {
            if (!validateContact()) return;
            fillReview();
            showStep(2);
            return;
        }
        if (currentStep === 2) {
            placeOrder();
        }
    }

    function placeOrder() {
        const btn = document.getElementById('btnNext');
        btn.disabled = true;
        btn.textContent = "{{ __('webapp.loading') }}";

        const body = {
            chat_id: userId,
            first_name: document.getElementById('inp_first_name').value.trim(),
            last_name: document.getElementById('inp_last_name').value.trim(),
            patronymic: document.getElementById('inp_patronymic').value.trim(),
            phone: document.getElementById('inp_phone').value.trim(),
            email: document.getElementById('inp_email').value.trim(),
            comment: document.getElementById('inp_comment').value.trim(),
        };

        fetch('/api/webapp/order/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showStep(3);
                if (window.tg && tg.HapticFeedback) tg.HapticFeedback.notificationOccurred('success');
            } else {
                showError(data.message || data.msg || 'Ошибка оформления заказа');
                btn.disabled = false;
                btn.textContent = "{{ __('webapp.place_order') }}";
            }
        })
        .catch(() => {
            showError('Ошибка сети');
            btn.disabled = false;
            btn.textContent = "{{ __('webapp.place_order') }}";
        });
    }

    showStep(1);
</script>
@endsection
