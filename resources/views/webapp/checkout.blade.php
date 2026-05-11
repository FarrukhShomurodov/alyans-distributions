@extends('webapp.layout')

@section('title', __('webapp.checkout_title'))
@section('nav'){{-- Hide nav --}}@endsection

@section('content')
    @php
        $tgUser = null;
        if(isset($user) && $user) {
            $tgUser = $user;
        }
    @endphp

    {{-- STEP INDICATOR --}}
    <div class="page-header">
        <a href="{{ route('webapp.cart', ['chat_id' => request('chat_id')]) }}"
           class="page-header__back"><i data-lucide="arrow-left"></i></a>
        <div class="page-header__title">{{ __('webapp.checkout_title') }}</div>
    </div>

    <div class="checkout-steps" id="stepsIndicator">
        <div class="checkout-step active" data-step="1">
            <span class="checkout-step__num">1</span>
            <span>{{ __('webapp.step_contact') }}</span>
        </div>
        <div class="checkout-step__line"></div>
        <div class="checkout-step" data-step="2">
            <span class="checkout-step__num">2</span>
            <span>{{ __('webapp.step_delivery') }}</span>
        </div>
        <div class="checkout-step__line"></div>
        <div class="checkout-step" data-step="3">
            <span class="checkout-step__num">3</span>
            <span>{{ __('webapp.step_payment') }}</span>
        </div>
        <div class="checkout-step__line"></div>
        <div class="checkout-step" data-step="4">
            <span class="checkout-step__num">4</span>
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
                       placeholder="+7 (___) ___-__-__" required>
                <div class="form-group__error">{{ __('webapp.invalid_phone') }}</div>
            </div>

            <div class="form-group">
                <label class="form-group__label">{{ __('webapp.email_address') }} <span class="required">*</span></label>
                <input type="email" class="form-group__input" id="inp_email"
                       value="{{ $user->saved_email ?? '' }}"
                       placeholder="email@example.com" required>
                <div class="form-group__error">{{ __('webapp.invalid_email') }}</div>
            </div>

            <div class="form-group">
                <label class="form-group__label">{{ __('webapp.order_comment') }}</label>
                <textarea class="form-group__input" id="inp_comment"
                          placeholder="{{ __('webapp.order_comment') }}" rows="3"></textarea>
            </div>
        </div>

        {{-- ============ STEP 2: DELIVERY (fullscreen map) ============ --}}
        <div class="checkout-panel" id="step2">

            {{-- Tabs: Пункты выдачи / Курьером --}}
            <div class="delivery-tabs">
                <button class="delivery-tab active" data-tab="pvz" onclick="switchDeliveryTab('pvz')">{{ __('webapp.tab_pvz') }}</button>
                <button class="delivery-tab" data-tab="courier" onclick="switchDeliveryTab('courier')">{{ __('webapp.tab_courier') }}</button>
            </div>

            {{-- === PVZ TAB === --}}
            <div class="delivery-tab-content active" id="tabPvz">
                {{-- Search bar above map --}}
                <div class="delivery-search-bar">
                    <input type="text" id="pvzAddressSearch" placeholder="{{ __('webapp.search_city') }}" autocomplete="off">
                    <button type="button" class="delivery-search-btn" onclick="triggerPvzSearch()">
                        <i data-lucide="search" style="width:18px;height:18px"></i>
                    </button>
                </div>
                <div class="delivery-map-wrap">
                    <div id="pvzMap" class="delivery-map-full"></div>
                </div>

                {{-- PVZ provider selector --}}
                <div class="pvz-providers">
                    <button class="pvz-provider active" data-provider="cdek" onclick="selectPvzProvider('cdek')">
                        <span class="pvz-provider__dot pvz-provider__dot--cdek"></span> СДЭК
                    </button>
                    <button class="pvz-provider" data-provider="yandex" onclick="selectPvzProvider('yandex')">
                        <span class="pvz-provider__dot pvz-provider__dot--yandex"></span> {{ __('webapp.yandex_short') }}
                    </button>
                </div>

                {{-- PVZ List (scrollable) --}}
                <div id="pvzList" class="pvz-list" style="display:none"></div>

                {{-- Bottom sheet: selected PVZ info + button --}}
                <div class="delivery-bottom-sheet" id="pvzBottomSheet" style="display:none">
                    <div class="delivery-bottom-sheet__row">
                        <div class="delivery-bottom-sheet__icon">
                            <i data-lucide="map-pin" style="width:24px;height:24px;color:var(--accent-light)"></i>
                        </div>
                        <div class="delivery-bottom-sheet__info">
                            <div class="delivery-bottom-sheet__name" id="pvzSheetName">—</div>
                            <div class="delivery-bottom-sheet__address" id="pvzSheetAddr">—</div>
                            <div class="delivery-bottom-sheet__delivery" id="pvzSheetDelivery" style="font-size:13px;color:var(--text-primary);margin-top:4px"></div>
                            <div class="delivery-bottom-sheet__cost" id="pvzSheetCost"></div>
                        </div>
                    </div>
                    <button class="delivery-select-btn" onclick="confirmPvzSelection()">
                        {{ __('webapp.select_pvz') }}
                    </button>
                </div>

                {{-- Delivery info card (shown after PVZ confirmed) --}}
                <div class="delivery-info-card" id="pvzInfoCard" style="display:none">
                    <div class="delivery-info-card__title">{{ __('webapp.delivery_info') }}</div>
                    <div class="delivery-info-card__row">
                        <span class="delivery-info-card__label">{{ __('webapp.delivery_type') }}</span>
                        <span class="delivery-info-card__value" id="pvzInfoMethod">—</span>
                    </div>
                    <div class="delivery-info-card__row">
                        <span class="delivery-info-card__label">{{ __('webapp.delivery_address_label') }}</span>
                        <span class="delivery-info-card__value" id="pvzInfoAddr">—</span>
                    </div>
                    <div class="delivery-info-card__row">
                        <span class="delivery-info-card__label">{{ __('webapp.delivery_cost') }}</span>
                        <span class="delivery-info-card__value" id="pvzInfoCost">—</span>
                    </div>
                </div>
            </div>

            {{-- === COURIER TAB === --}}
            <div class="delivery-tab-content" id="tabCourier">
                <p class="courier-notice">
                    <i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0"></i>
                    {{ __('webapp.courier_moscow_only') }}
                </p>
                <div class="delivery-search-bar">
                    <input type="text" id="inp_courier_address" placeholder="{{ __('webapp.search_address') }}" autocomplete="off">
                    <button type="button" class="delivery-search-btn" onclick="triggerCourierSearch()">
                        <i data-lucide="search" style="width:18px;height:18px"></i>
                    </button>
                </div>
                <div class="delivery-map-wrap">
                    <div id="courierMap" class="delivery-map-full"></div>
                </div>

                {{-- Courier bottom sheet with button (компактный) --}}
                <div class="delivery-bottom-sheet delivery-bottom-sheet--compact" id="courierBottomSheet" style="display:none">
                    <div class="delivery-bottom-sheet__compact-addr">
                        <i data-lucide="map-pin" style="width:16px;height:16px;color:var(--accent-light);flex-shrink:0"></i>
                        <span id="courierSheetAddr">—</span>
                    </div>
                    <button class="delivery-select-btn" onclick="confirmCourierSelection()">
                        {{ __('webapp.deliver_here') }}
                    </button>
                </div>

                {{-- Courier extra fields (shown after "Привезти сюда") --}}
                <div class="courier-extra-fields" id="courierExtraFields" style="display:none">
                    <div class="courier-fields-grid">
                        <div class="form-group">
                            <label class="form-group__label">{{ __('webapp.apartment') }}</label>
                            <input type="text" class="form-group__input" id="inp_apartment" placeholder="—">
                        </div>
                        <div class="form-group">
                            <label class="form-group__label">{{ __('webapp.floor') }}</label>
                            <input type="text" class="form-group__input" id="inp_floor" placeholder="—">
                        </div>
                        <div class="form-group">
                            <label class="form-group__label">{{ __('webapp.entrance') }}</label>
                            <input type="text" class="form-group__input" id="inp_entrance" placeholder="—">
                        </div>
                        <div class="form-group">
                            <label class="form-group__label">{{ __('webapp.intercom') }}</label>
                            <input type="text" class="form-group__input" id="inp_intercom" placeholder="—">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-group__label">{{ __('webapp.delivery_date') }}</label>
                        <input type="date" class="form-group__input" id="inp_delivery_date"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               max="{{ date('Y-m-d', strtotime('+14 days')) }}"
                               onchange="validateCourierDate(this)">
                        <div class="form-group__hint" style="font-size:11px;color:var(--text-muted);margin-top:4px">
                            ⚠️ В воскресенье доставка не осуществляется. Максимум на 2 недели вперёд.
                        </div>
                    </div>

                    {{-- Delivery info card for courier --}}
                    <div class="delivery-info-card" id="courierInfoCard">
                        <div class="delivery-info-card__title">{{ __('webapp.delivery_info') }}</div>
                        <div class="delivery-info-card__row">
                            <span class="delivery-info-card__label">{{ __('webapp.delivery_type') }}</span>
                            <span class="delivery-info-card__value">{{ __('webapp.delivery_courier') }}</span>
                        </div>
                        <div class="delivery-info-card__row">
                            <span class="delivery-info-card__label">{{ __('webapp.delivery_address_label') }}</span>
                            <span class="delivery-info-card__value" id="courierInfoAddr">—</span>
                        </div>
                        <div class="delivery-info-card__row">
                            <span class="delivery-info-card__label">{{ __('webapp.delivery_cost') }}</span>
                            <span class="delivery-info-card__value" id="courierInfoCost">400 ₽</span>
                        </div>
                        <div class="delivery-info-card__row" id="courierFreeHint" style="display:none">
                            <span class="delivery-info-card__label" style="color:var(--success)">🎁 Бесплатная доставка</span>
                            <span class="delivery-info-card__value" style="color:var(--success)">от 5 000 ₽</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ============ STEP 3: PAYMENT ============ --}}
        <div class="checkout-panel" id="step3">
            <div class="checkout-panel__title">{{ __('webapp.payment_info') }}</div>

            <div id="paymentNote" class="delivery-info-card" style="margin-bottom:16px">
                <p style="font-size:13px;color:var(--text-secondary);line-height:1.5" id="paymentNoteText">
                    {{ __('webapp.payment_cdek_note') }}
                </p>
            </div>

            <div id="paymentMethodsBlock">
                <div class="checkout-panel__title" style="font-size:15px">{{ __('webapp.payment_methods') }}</div>
                <div class="delivery-methods">
                    <div class="delivery-method-card" data-pay="cash" onclick="selectPayment('cash')">
                        <div class="delivery-method-card__icon" style="background:var(--accent-soft);color:var(--accent)">
                            <i data-lucide="banknote" style="width:22px;height:22px"></i>
                        </div>
                        <div class="delivery-method-card__text">
                            <div class="delivery-method-card__name">{{ __('webapp.cash') }}</div>
                            <div class="delivery-method-card__desc">При получении</div>
                        </div>
                        <div class="delivery-method-card__radio"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ STEP 4: REVIEW & CONFIRM ============ --}}
        <div class="checkout-panel" id="step4">
            <div class="checkout-panel__title">{{ __('webapp.review_title') }}</div>

            {{-- Contact info review --}}
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
                <div class="review-section__row">
                    <span class="review-section__label">{{ __('webapp.email_address') }}</span>
                    <span class="review-section__value" id="rv_email">—</span>
                </div>
            </div>

            {{-- Delivery review --}}
            <div class="review-section">
                <div class="review-section__title">{{ __('webapp.review_delivery') }}</div>
                <div class="review-section__row">
                    <span class="review-section__label">{{ __('webapp.delivery_type') }}</span>
                    <span class="review-section__value" id="rv_delivery_method">—</span>
                </div>
                <div class="review-section__row">
                    <span class="review-section__label">{{ __('webapp.delivery_address_label') }}</span>
                    <span class="review-section__value" id="rv_delivery_address">—</span>
                </div>
                <div class="review-section__row" id="rv_delivery_cost_row">
                    <span class="review-section__label">{{ __('webapp.delivery_cost') }}</span>
                    <span class="review-section__value" id="rv_delivery_cost">—</span>
                </div>
            </div>

            {{-- Items review --}}
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
                        <div class="review-item__price">{{ number_format($up * $item->quantity, 0, '.', ' ') }} ₽</div>
                    </div>
                @endforeach
            </div>

            {{-- Payment review --}}
{{--            <div class="review-section">--}}
{{--                <div class="review-section__title">{{ __('webapp.review_payment') }}</div>--}}
{{--                <div class="review-section__row">--}}
{{--                    <span class="review-section__label">{{ __('webapp.payment_methods') }}</span>--}}
{{--                    <span class="review-section__value" id="rv_payment">PayMe</span>--}}
{{--                </div>--}}
{{--            </div>--}}

            {{-- Comment --}}
            <div class="review-section" id="rv_comment_section" style="display:none">
                <div class="review-section__title">{{ __('webapp.review_comment') }}</div>
                <p style="font-size:13px;color:var(--text-secondary)" id="rv_comment_text"></p>
            </div>

            {{-- Promo --}}
            @if($cart->promoCode)
            <div class="review-section">
                <div class="review-section__title">{{ __('webapp.review_promo') }}</div>
                <div class="review-section__row">
                    <span class="review-section__label">{{ $cart->promoCode->code }}</span>
                    <span class="review-section__value" style="color:var(--success)">
                        −{{ number_format($pricing['promo_code_discount'], 0, '.', ' ') }} ₽
                    </span>
                </div>
            </div>
            @endif

            {{-- Totals --}}
            <div class="review-totals">
                <div class="review-totals__row">
                    <span>{{ __('webapp.items_total') }}</span>
                    <span>{{ number_format($pricing['subtotal'], 0, '.', ' ') }} ₽</span>
                </div>
                @if(($pricing['volume_tier_percent'] ?? 0) > 0)
                <div class="review-totals__row review-totals__row--discount">
                    <span>{{ __('webapp.volume_discount') }} ({{ $pricing['volume_tier_percent'] }}%)</span>
                    <span>−{{ number_format($pricing['volume_discount'], 0, '.', ' ') }} ₽</span>
                </div>
                @endif
                @if($pricing['discount_total'] > 0)
                <div class="review-totals__row review-totals__row--discount">
                    <span>{{ __('webapp.discount') }}</span>
                    <span>−{{ number_format($pricing['discount_total'], 0, '.', ' ') }} ₽</span>
                </div>
                @endif
                <div class="review-totals__row" id="rv_delivery_total_row">
                    <span>{{ __('webapp.delivery_total') }}</span>
                    <span id="rv_delivery_total">0 ₽</span>
                </div>
                <div class="review-totals__row review-totals__row--total">
                    <span>{{ __('webapp.total') }}</span>
                    <span id="rv_grand_total">{{ number_format($pricing['total'], 0, '.', ' ') }} ₽</span>
                </div>
            </div>
        </div>

        {{-- ============ STEP 5: SUCCESS (hidden until order placed) ============ --}}
        <div class="checkout-panel" id="step5">
            <div class="order-success">
                <div class="order-success__icon">🎉</div>
                <div class="order-success__title">{{ __('webapp.order_complete_title') }}</div>
                <div class="order-success__text">{{ __('webapp.order_complete_text') }}</div>
                <div class="order-success__hours">
                    <strong>{{ __('webapp.order_complete_hours') }}</strong><br>
                    {{ __('webapp.order_complete_weekdays') }}<br>
                    {{ __('webapp.order_complete_saturday') }}<br>
                    {{ __('webapp.order_complete_sunday') }}
                </div>
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
{{-- Yandex Maps JS API --}}
@if(config('services.yandex.geocoder_key'))
<script src="https://api-maps.yandex.ru/2.1/?apikey={{ config('services.yandex.geocoder_key') }}&lang=ru_RU&suggest_apikey={{ config('services.yandex.geocoder_key') }}&load=package.full"></script>
@else
<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&load=package.full"></script>
@endif
<script>
    const csrf = "{{ csrf_token() }}";
    const pricingTotal = {{ $pricing['total'] }};
    let currentStep = 1;
    let selectedPayment = 'cash';
    let selectedPvz = null;
    let deliveryPrice = 0;
    let deliveryConfirmed = false;

    var hasYandexApiKey = {{ config('services.yandex.geocoder_key') ? 'true' : 'false' }};

    // Saved delivery data for auto-fill
    var savedDelivery = {
        address: {!! json_encode($user->saved_delivery_address ?? '') !!},
        city: {!! json_encode($user->saved_delivery_city ?? '') !!},
        method: {!! json_encode($user->saved_delivery_method ?? '') !!},
        apartment: {!! json_encode($user->saved_delivery_apartment ?? '') !!},
        floor: {!! json_encode($user->saved_delivery_floor ?? '') !!},
        entrance: {!! json_encode($user->saved_delivery_entrance ?? '') !!},
        intercom: {!! json_encode($user->saved_delivery_intercom ?? '') !!}
    };

    /* ========== YANDEX MAPS ========== */
    let mapPvz = null, mapCourier = null;
    let pvzPin = null, courierPin = null;
    let pvzMarkers = [];
    let currentPvzProvider = 'cdek';
    let currentDeliveryTab = 'pvz';
    let lastCourierCoords = null; // store coords for Moscow check

    // Flash input to show address was inserted
    function flashInput(el) {
        el.style.transition = 'background 0.3s';
        el.style.background = 'rgba(0, 166, 81, 0.18)';
        setTimeout(function() { el.style.background = ''; }, 800);
    }

    // Полигон МКАД (детальный, по часовой стрелке от северо-запада)
    // Точки взяты по реальной трассе МКАД с небольшим запасом наружу,
    // чтобы все адреса вблизи МКАД попадали в зону доставки
    var MKAD_POLYGON = [
        // Северная часть (запад → восток)
        [55.916, 37.376], [55.918, 37.426], [55.917, 37.470], [55.918, 37.526],
        [55.917, 37.583], [55.917, 37.638], [55.916, 37.683], [55.911, 37.737],
        // Северо-восток / восток (сверху → вниз)
        [55.901, 37.770], [55.879, 37.805], [55.851, 37.838], [55.815, 37.850],
        [55.770, 37.847], [55.731, 37.842], [55.690, 37.838], [55.660, 37.825],
        // Южная часть (восток → запад) — уточнённый контур чтобы захватить
        // Капотню, Братеево, Орехово-Борисово, Зябликово, Бирюлёво и т.д.
        [55.633, 37.812], [55.615, 37.795], [55.598, 37.770], [55.595, 37.735],
        [55.593, 37.700], [55.585, 37.670], [55.578, 37.625], [55.572, 37.580],
        [55.572, 37.530], [55.578, 37.480], [55.586, 37.435], [55.601, 37.405],
        // Запад (юг → север)
        [55.626, 37.378], [55.659, 37.355], [55.694, 37.343], [55.733, 37.342],
        [55.771, 37.347], [55.812, 37.350], [55.850, 37.355], [55.884, 37.363]
    ];

    // Bounding box для быстрой предварительной проверки (немного шире самого полигона)
    var MOSCOW_BOUNDS = {latMin:55.55, latMax:55.93, lngMin:37.30, lngMax:37.87};

    /**
     * Алгоритм ray casting: точка внутри полигона?
     */
    function pointInPolygon(coords, polygon) {
        var lat = coords[0], lng = coords[1];
        var inside = false;
        for (var i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            var xi = polygon[i][0], yi = polygon[i][1];
            var xj = polygon[j][0], yj = polygon[j][1];
            var intersect = ((yi > lng) !== (yj > lng)) &&
                            (lat < (xj - xi) * (lng - yi) / (yj - yi) + xi);
            if (intersect) inside = !inside;
        }
        return inside;
    }

    function isMoscow(coords) {
        if (!coords || coords.length < 2) return false;
        // Быстрый bbox-фильтр
        if (coords[0] < MOSCOW_BOUNDS.latMin || coords[0] > MOSCOW_BOUNDS.latMax ||
            coords[1] < MOSCOW_BOUNDS.lngMin || coords[1] > MOSCOW_BOUNDS.lngMax) {
            return false;
        }
        // Точная проверка — внутри МКАД
        return pointInPolygon(coords, MKAD_POLYGON);
    }

    function whenYmapsReady(fn) {
        if (typeof ymaps !== 'undefined') ymaps.ready(fn);
    }

    function createMap(containerId, center, zoom) {
        var el = document.getElementById(containerId);
        if (!el || el.offsetWidth === 0 || el.offsetHeight === 0) return null;
        try {
            return new ymaps.Map(containerId, {
                center: center || [55.751244, 37.618423],
                zoom: zoom || 10,
                controls: ['zoomControl', 'geolocationControl']
            }, {suppressMapOpenBlock:true});
        } catch(e) { console.warn('Map init error:', e); return null; }
    }

    function tryGeolocation(map, onSuccess) {
        if (!map || !navigator.geolocation) {
            if (onSuccess) onSuccess([55.751244, 37.618423]);
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function(p){
                var coords = [p.coords.latitude, p.coords.longitude];
                map.setCenter(coords, 13, {duration:300});
                if (onSuccess) onSuccess(coords);
            },
            function(){
                if (onSuccess) onSuccess([55.751244, 37.618423]);
            },
            {enableHighAccuracy:false, timeout:5000}
        );
    }

    /* ========== SERVER-SIDE GEOCODING (Yandex HTTP Geocoder API) ========== */
    function reverseGeocode(coords, cb) {
        var lat = coords[0], lng = coords[1];
        console.log('[reverseGeocode] request:', lat, lng);
        fetch('/api/webapp/geo/reverse?lat=' + lat + '&lng=' + lng)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                console.log('[reverseGeocode] response:', data);
                if (data.address) {
                    cb(data.address, data.city || '');
                } else {
                    console.warn('[reverseGeocode] empty address');
                }
            })
            .catch(function(err) {
                console.error('[reverseGeocode] fetch error:', err);
            });
    }

    function forwardGeocode(addr, cb) {
        console.log('[forwardGeocode] request:', addr);
        fetch('/api/webapp/geo/forward?q=' + encodeURIComponent(addr))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                console.log('[forwardGeocode] response:', data);
                if (data.lat && data.lng) {
                    cb([data.lat, data.lng], data.address || addr, data.city || '');
                } else {
                    console.warn('[forwardGeocode] no coords');
                }
            })
            .catch(function(err) {
                console.error('[forwardGeocode] fetch error:', err);
            });
    }

    /* ========== TAB / PROVIDER SWITCHING ========== */
    function switchDeliveryTab(tab) {
        currentDeliveryTab = tab;
        selectedPvz = null;
        deliveryPrice = 0;
        deliveryConfirmed = false;

        document.querySelectorAll('.delivery-tab').forEach(function(t) {
            t.classList.toggle('active', t.dataset.tab === tab);
        });
        document.querySelectorAll('.delivery-tab-content').forEach(function(c) {
            c.classList.remove('active');
        });

        if (tab === 'pvz') {
            document.getElementById('tabPvz').classList.add('active');
            setTimeout(function() { initPvzMap(); }, 100);
        } else {
            document.getElementById('tabCourier').classList.add('active');
            setTimeout(function() { initCourierMap(); }, 100);
        }

        // Hide all sheets/info
        document.getElementById('pvzBottomSheet').style.display = 'none';
        document.getElementById('pvzInfoCard').style.display = 'none';
        document.getElementById('courierBottomSheet').style.display = 'none';
        document.getElementById('courierExtraFields').style.display = 'none';

        lucide.createIcons();
    }

    function selectPvzProvider(provider) {
        currentPvzProvider = provider;
        selectedPvz = null;
        deliveryPrice = 0;
        deliveryConfirmed = false;

        document.querySelectorAll('.pvz-provider').forEach(function(b) {
            b.classList.toggle('active', b.dataset.provider === provider);
        });

        // Clear PVZ list, bottom sheet, info card
        document.getElementById('pvzList').style.display = 'none';
        document.getElementById('pvzList').innerHTML = '';
        document.getElementById('pvzBottomSheet').style.display = 'none';
        document.getElementById('pvzInfoCard').style.display = 'none';

        // Clear markers from map
        if (mapPvz) {
            pvzMarkers.forEach(function(m) { mapPvz.geoObjects.remove(m); });
            pvzMarkers = [];
            if (pvzPin) { mapPvz.geoObjects.remove(pvzPin); pvzPin = null; }
        }

        document.getElementById('pvzAddressSearch').value = '';

        // Load PVZ list for the newly selected provider using current map center
        if (mapPvz) {
            setTimeout(function() {
                loadPvzForCenter();
            }, 100);
        }

        tg.HapticFeedback.selectionChanged();
    }

    /* -- Derive delivery method string for API -- */
    function getDeliveryMethod() {
        if (currentDeliveryTab === 'courier') return 'courier';
        return currentPvzProvider === 'yandex' ? 'yandex_pvz' : 'cdek_pvz';
    }

    function loadPvzForCenter() {
        if (!mapPvz) return;
        var center = mapPvz.getCenter();
        if (!center || center.length < 2) return;
        if (currentPvzProvider === 'yandex') {
            reverseGeocode(center, function(address, city) {
                loadYandexPvz(center[0], center[1], city || address);
            });
        } else {
            reverseGeocode(center, function(address, city) {
                loadCdekPvz(city || address || '', center[0], center[1]);
            });
        }
    }

    function loadPvzForCoords(lat, lng) {
        if (currentPvzProvider === 'yandex') {
            reverseGeocode([lat, lng], function(address, city) {
                loadYandexPvz(lat, lng, city || address);
            });
        } else {
            reverseGeocode([lat, lng], function(address, city) {
                loadCdekPvz(city || address || '', lat, lng);
            });
        }
    }

    /* ========== SUGGEST VIEW (autocomplete for address inputs) ========== */
    var pvzSuggest = null, courierSuggest = null;
    var suggestFailed = false;

    function initSuggestViews() {
        whenYmapsReady(function() {
            // SuggestView only works with a valid Yandex Maps API key
            if (hasYandexApiKey) {
                try {
                    if (!pvzSuggest) {
                        pvzSuggest = new ymaps.SuggestView('pvzAddressSearch', {results: 5});
                        pvzSuggest.events.add('select', function(e) {
                            var q = e.get('item').value;
                            forwardGeocode(q, function(coords, fullAddress, city) {
                                placePvzPin(coords);
                                document.getElementById('pvzAddressSearch').value = fullAddress;
                                if (currentPvzProvider === 'yandex') {
                                    loadYandexPvz(coords[0], coords[1], city || fullAddress || q);
                                } else {
                                    loadCdekPvz(city || q, coords[0], coords[1]);
                                }
                            });
                        });
                    }
                    if (!courierSuggest) {
                        courierSuggest = new ymaps.SuggestView('inp_courier_address', {results: 5});
                        courierSuggest.events.add('select', function(e) {
                            var q = e.get('item').value;
                            forwardGeocode(q, function(coords, fullAddress, city) {
                                if (!isMoscow(coords)) {
                                    showError("{{ __('webapp.courier_moscow_only') }}");
                                    tg.HapticFeedback.notificationOccurred("error");
                                    return;
                                }
                                lastCourierCoords = coords;
                                placeCourierPin(coords);
                                var inp = document.getElementById('inp_courier_address');
                                inp.value = fullAddress;
                                flashInput(inp);
                                showCourierSheet(fullAddress);
                            });
                        });
                    }
                    console.log('[SuggestView] initialized');
                } catch(e) {
                    console.warn('[SuggestView] error:', e);
                }
            }
            // Always enable debounce search (type address → auto-search via Nominatim)
            enableDebounceFallback();
        });
    }

    /* Debounced search: works alongside SuggestView or as standalone fallback */
    var _debounceFallbackInit = false;
    function enableDebounceFallback() {
        if (_debounceFallbackInit) return;
        _debounceFallbackInit = true;

        var pvzTimeout = null;
        document.getElementById('pvzAddressSearch')?.addEventListener('input', function() {
            clearTimeout(pvzTimeout);
            var q = this.value.trim();
            if (q.length < 3) return;
            pvzTimeout = setTimeout(function() { searchPvzAddress(q); }, 800);
        });
        var courierTimeout = null;
        document.getElementById('inp_courier_address')?.addEventListener('input', function() {
            clearTimeout(courierTimeout);
            var q = this.value.trim();
            if (q.length < 3) return;
            courierTimeout = setTimeout(function() { searchCourierAddress(q); }, 800);
        });
    }

    /* ========== PVZ MAP (shared for CDEK + Yandex) ========== */
    function initPvzMap() {
        if (mapPvz) {
            mapPvz.container.fitToViewport();
            loadPvzForCenter();
            return;
        }
        whenYmapsReady(function() {
            mapPvz = createMap('pvzMap', [55.751244, 37.618423], 10);
            if (!mapPvz) return;
            tryGeolocation(mapPvz, function(coords) {
                if ((savedDelivery.address || savedDelivery.city) && savedDelivery.method !== 'courier') {
                    var query = savedDelivery.city || savedDelivery.address;
                    document.getElementById('pvzAddressSearch').value = query;
                    searchPvzAddress(query);
                } else {
                    loadPvzForCoords(coords[0], coords[1]);
                }
            });
            initSuggestViews();

            mapPvz.events.add('click', function(e) {
                var coords = e.get('coords');
                placePvzPin(coords);
                reverseGeocode(coords, function(address, city) {
                    var inp = document.getElementById('pvzAddressSearch');
                    inp.value = address;
                    flashInput(inp);
                    if (currentPvzProvider === 'yandex') {
                        loadYandexPvz(coords[0], coords[1], city || address);
                    } else {
                        loadCdekPvz(city || address, coords[0], coords[1]);
                    }
                });
            });
        });
    }

    function placePvzPin(coords) {
        if (!mapPvz) return;
        if (pvzPin) mapPvz.geoObjects.remove(pvzPin);
        pvzPin = new ymaps.Placemark(coords, {}, {preset:'islands#redDotIcon', draggable:true});
        mapPvz.geoObjects.add(pvzPin);
        mapPvz.setCenter(coords, 14, {duration:300});

        pvzPin.events.add('dragend', function() {
            var c = pvzPin.geometry.getCoordinates();
            reverseGeocode(c, function(address, city) {
                var inp = document.getElementById('pvzAddressSearch');
                inp.value = address;
                flashInput(inp);
                if (currentPvzProvider === 'yandex') {
                    loadYandexPvz(c[0], c[1], city || address);
                } else {
                    loadCdekPvz(city || address, c[0], c[1]);
                }
            });
        });
    }

    /* ========== COURIER MAP ========== */
    function initCourierMap() {
        if (mapCourier) { mapCourier.container.fitToViewport(); return; }
        whenYmapsReady(function() {
            mapCourier = createMap('courierMap', [55.751244, 37.618423], 11);
            if (!mapCourier) return;
            initSuggestViews();

            mapCourier.events.add('click', function(e) {
                var coords = e.get('coords');
                handleCourierCoords(coords);
            });
        });
    }

    function handleCourierCoords(coords) {
        if (!isMoscow(coords)) {
            showError("{{ __('webapp.courier_moscow_only') }}");
            tg.HapticFeedback.notificationOccurred("error");
            return;
        }
        lastCourierCoords = coords;
        placeCourierPin(coords);
        reverseGeocode(coords, function(address) {
            var inp = document.getElementById('inp_courier_address');
            inp.value = address;
            flashInput(inp);
            showCourierSheet(address);
        });
    }

    function placeCourierPin(coords) {
        if (!mapCourier) return;
        if (courierPin) mapCourier.geoObjects.remove(courierPin);
        courierPin = new ymaps.Placemark(coords, {}, {preset:'islands#blueDotIcon', draggable:true});
        mapCourier.geoObjects.add(courierPin);
        mapCourier.setCenter(coords, 15, {duration:300});

        courierPin.events.add('dragend', function() {
            var c = courierPin.geometry.getCoordinates();
            if (!isMoscow(c)) {
                showError("{{ __('webapp.courier_moscow_only') }}");
                tg.HapticFeedback.notificationOccurred("error");
                // Move pin back
                if (lastCourierCoords) {
                    courierPin.geometry.setCoordinates(lastCourierCoords);
                }
                return;
            }
            lastCourierCoords = c;
            reverseGeocode(c, function(address) {
                var inp = document.getElementById('inp_courier_address');
                inp.value = address;
                flashInput(inp);
                showCourierSheet(address);
            });
        });
    }

    /* ========== BOTTOM SHEETS ========== */
    /**
     * Форматирует диапазон дат доставки в человекочитаемый вид:
     * formatDeliveryRange(2, 3) → "5 мая – 6 мая"
     * formatDeliveryRange(1, 1) → "4 мая"
     */
    function formatDeliveryRange(minDays, maxDays) {
        var months = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
        var today = new Date();
        var minDate = new Date(today);
        minDate.setDate(today.getDate() + Number(minDays || 0));
        var maxDate = new Date(today);
        maxDate.setDate(today.getDate() + Number(maxDays || 0));

        var minStr = minDate.getDate() + ' ' + months[minDate.getMonth()];
        var maxStr = maxDate.getDate() + ' ' + months[maxDate.getMonth()];
        return minStr === maxStr ? minStr : (minStr + ' – ' + maxStr);
    }

    function showPvzSheet(name, addr, cost) {
        deliveryConfirmed = false;
        document.getElementById('pvzInfoCard').style.display = 'none';
        document.getElementById('pvzSheetName').textContent = name;
        document.getElementById('pvzSheetAddr').textContent = addr;
        document.getElementById('pvzSheetCost').textContent = cost;
        document.getElementById('pvzBottomSheet').style.display = 'flex';
        lucide.createIcons();
        // Scroll to bottom sheet
        document.getElementById('pvzBottomSheet').scrollIntoView({behavior:'smooth', block:'end'});
    }

    function showCourierSheet(addr) {
        deliveryConfirmed = false;
        document.getElementById('courierExtraFields').style.display = 'none';
        document.getElementById('courierSheetAddr').textContent = addr;
        document.getElementById('courierBottomSheet').style.display = 'flex';
        lucide.createIcons();
        document.getElementById('courierBottomSheet').scrollIntoView({behavior:'smooth', block:'end'});
    }

    /* ========== CONFIRM BUTTONS ========== */
    function confirmPvzSelection() {
        if (!selectedPvz) {
            showError("{{ __('webapp.select_pvz') }}");
            return;
        }
        deliveryConfirmed = true;
        tg.HapticFeedback.impactOccurred("medium");

        // Скрываем плавающую панель — она больше не нужна
        document.getElementById('pvzBottomSheet').style.display = 'none';

        // Show delivery info card
        var method = getDeliveryMethod();
        var infoCard = document.getElementById('pvzInfoCard');
        document.getElementById('pvzInfoMethod').textContent =
            method === 'cdek_pvz' ? '{{ __("webapp.cdek_pvz") }}' : '{{ __("webapp.yandex_pvz") }}';
        document.getElementById('pvzInfoAddr').textContent = selectedPvz.address || '—';
        if (deliveryPrice > 0) {
            document.getElementById('pvzInfoCost').textContent =
                '~' + Math.round(deliveryPrice) + ' ₽ (' + "{{ __('webapp.approximate_cost') }}" + ')';
        } else if (method === 'yandex_pvz') {
            document.getElementById('pvzInfoCost').textContent = 'Рассчитывается Яндексом при отправке';
        } else {
            document.getElementById('pvzInfoCost').textContent = '—';
        }
        infoCard.style.display = 'block';
        infoCard.scrollIntoView({behavior:'smooth', block:'end'});
    }

    var COURIER_PRICE = 400; // фиксированная стоимость курьерской доставки по Москве
    var COURIER_FREE_FROM = 5000; // от какой суммы заказа доставка бесплатная

    // Высчитывает стоимость курьерской доставки с учётом бесплатной доставки от 5000₽
    function getCourierPrice() {
        return pricingTotal >= COURIER_FREE_FROM ? 0 : COURIER_PRICE;
    }

    // Запрет выбора воскресенья и дат дальше 2 недель
    function validateCourierDate(input) {
        if (!input.value) return;
        var d = new Date(input.value + 'T00:00:00');
        var today = new Date();
        today.setHours(0,0,0,0);
        var maxDate = new Date(today);
        maxDate.setDate(maxDate.getDate() + 14);

        if (d > maxDate) {
            showError("Дату доставки можно выбрать не дальше чем на 2 недели вперёд.");
            tg.HapticFeedback.notificationOccurred("error");
            input.value = '';
            input.classList.add('error');
            setTimeout(function() { input.classList.remove('error'); }, 1500);
            return;
        }

        if (d.getDay() === 0) { // 0 = воскресенье
            showError("В воскресенье доставка не осуществляется. Выберите другой день.");
            tg.HapticFeedback.notificationOccurred("error");
            input.value = '';
            input.classList.add('error');
            setTimeout(function() { input.classList.remove('error'); }, 1500);
        }
    }

    function confirmCourierSelection() {
        var addr = document.getElementById('inp_courier_address').value.trim();
        if (!addr) {
            showError("{{ __('webapp.search_address') }}");
            return;
        }
        deliveryConfirmed = true;
        // Курьер по Москве: 400₽, при заказе от 5000₽ — бесплатно
        deliveryPrice = getCourierPrice();
        tg.HapticFeedback.impactOccurred("medium");

        // Скрываем плавающую панель — больше не нужна
        document.getElementById('courierBottomSheet').style.display = 'none';

        // Show extra fields + info card
        document.getElementById('courierExtraFields').style.display = 'block';
        document.getElementById('courierInfoAddr').textContent = addr;

        // Обновляем строку стоимости в инфо-карточке
        var costEl = document.getElementById('courierInfoCost');
        var freeHint = document.getElementById('courierFreeHint');
        if (costEl) {
            if (deliveryPrice === 0) {
                costEl.innerHTML = '<span style="color:var(--success);font-weight:600">Бесплатно</span>';
                if (freeHint) freeHint.style.display = '';
            } else {
                costEl.textContent = COURIER_PRICE + ' ₽';
                if (freeHint) freeHint.style.display = 'none';
            }
        }

        lucide.createIcons();
        document.getElementById('courierExtraFields').scrollIntoView({behavior:'smooth', block:'start'});
    }

    /* ========== STEP NAVIGATION ========== */
    function goToStep(step) {
        currentStep = step;
        document.querySelectorAll('.checkout-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('step' + step).classList.add('active');

        document.querySelectorAll('.checkout-step').forEach(s => {
            var sn = parseInt(s.dataset.step);
            s.classList.remove('active', 'done');
            if (sn === step) s.classList.add('active');
            else if (sn < step) s.classList.add('done');
        });
        document.querySelectorAll('.checkout-step__line').forEach(function(line, i) {
            line.classList.toggle('done', i + 1 < step);
        });

        document.getElementById('btnBack').style.display = step > 1 ? 'flex' : 'none';

        var btn = document.getElementById('btnNext');
        var bar = document.getElementById('checkoutBar');
        if (step === 4) btn.textContent = "{{ __('webapp.place_order') }}";
        else if (step === 5) { bar.style.display = 'none'; document.getElementById('stepsIndicator').style.display = 'none'; }
        else btn.textContent = "{{ __('webapp.next_step') }}";

        // Init map when entering step 2 + auto-fill saved data
        if (step === 2) {
            // Auto-fill saved delivery data (only first time)
            if (!window._deliveryPrefilled && (savedDelivery.address || savedDelivery.city)) {
                window._deliveryPrefilled = true;
                if (savedDelivery.method === 'courier') {
                    switchDeliveryTab('courier');
                    document.getElementById('inp_courier_address').value = savedDelivery.address || savedDelivery.city;
                    if (savedDelivery.apartment) document.getElementById('inp_apartment').value = savedDelivery.apartment;
                    if (savedDelivery.floor) document.getElementById('inp_floor').value = savedDelivery.floor;
                    if (savedDelivery.entrance) document.getElementById('inp_entrance').value = savedDelivery.entrance;
                    if (savedDelivery.intercom) document.getElementById('inp_intercom').value = savedDelivery.intercom;
                } else {
                    document.getElementById('pvzAddressSearch').value = savedDelivery.city || savedDelivery.address;
                }
            }
            setTimeout(function() {
                if (currentDeliveryTab === 'pvz') initPvzMap();
                else initCourierMap();
            }, 300);
        }

        lucide.createIcons();
        window.scrollTo({top:0, behavior:'smooth'});
    }

    function nextStep() {
        if (currentStep === 1 && !validateContact()) return;
        if (currentStep === 2 && !validateDelivery()) return;
        if (currentStep === 3) populateReview();
        if (currentStep === 4) { placeOrder(); return; }
        goToStep(currentStep + 1);
    }

    function prevStep() { if (currentStep > 1) goToStep(currentStep - 1); }

    /* ========== CONTACT VALIDATION ========== */
    function validateContact() {
        var valid = true;
        [
            {id:'inp_first_name', required:true},
            {id:'inp_last_name', required:true},
            {id:'inp_phone', required:true, pattern:/^\+?\d[\d\s\-()]{7,}$/},
            {id:'inp_email', required:true, pattern:/^[^\s@]+@[^\s@]+\.[^\s@]+$/},
        ].forEach(function(f) {
            var el = document.getElementById(f.id);
            var val = el.value.trim();
            el.classList.remove('error');
            if (f.required && !val) { el.classList.add('error'); valid = false; }
            else if (f.pattern && val && !f.pattern.test(val)) { el.classList.add('error'); valid = false; }
        });
        if (!valid) tg.HapticFeedback.notificationOccurred("error");
        return valid;
    }

    /* ========== DELIVERY VALIDATION ========== */
    function validateDelivery() {
        var method = getDeliveryMethod();

        if (!deliveryConfirmed) {
            if (method === 'courier') {
                showError("{{ __('webapp.deliver_here') }}");
            } else {
                showError("{{ __('webapp.select_pvz') }}");
            }
            return false;
        }

        if ((method === 'cdek_pvz' || method === 'yandex_pvz') && !selectedPvz) {
            showError("{{ __('webapp.select_pvz') }}");
            return false;
        }
        if (method === 'courier') {
            var addr = document.getElementById('inp_courier_address').value.trim();
            if (!addr) {
                document.getElementById('inp_courier_address').classList.add('error');
                showError("{{ __('webapp.search_address') }}");
                return false;
            }
        }
        updatePaymentNote();
        return true;
    }

    function updatePaymentNote() {
        var el = document.getElementById('paymentNoteText');
        var methodsBlock = document.getElementById('paymentMethodsBlock');
        var method = getDeliveryMethod();

        if (method === 'yandex_pvz') {
            el.textContent = "{{ __('webapp.payment_yandex_note') }}";
            // При доставке в ПВЗ — только СБП-предоплата, выбора способов нет
            if (methodsBlock) methodsBlock.style.display = 'none';
            selectedPayment = 'sbp';
        } else if (method === 'cdek_pvz') {
            el.textContent = "{{ __('webapp.payment_cdek_note') }}";
            if (methodsBlock) methodsBlock.style.display = 'none';
            selectedPayment = 'sbp';
        } else {
            // Курьер — показываем выбор, доступны наличные при получении
            el.textContent = "{{ __('webapp.payment_courier_note') }}";
            if (methodsBlock) methodsBlock.style.display = '';
            selectedPayment = 'cash';
            // Активируем карточку "Наличными"
            document.querySelectorAll('[data-pay]').forEach(function(c) {
                c.classList.toggle('active', c.dataset.pay === 'cash');
            });
        }
    }

    /* ========== PAYMENT ========== */
    function selectPayment(method) {
        selectedPayment = method;
        document.querySelectorAll('[data-pay]').forEach(function(c) {
            c.classList.toggle('active', c.dataset.pay === method);
        });
    }

    /* ========== PVZ ADDRESS SEARCH ========== */
    function searchPvzAddress(q) {
        if (!q || q.length < 2) return;
        console.log('[searchPvzAddress]', q);

        var listEl = document.getElementById('pvzList');
        listEl.style.display = 'block';
        listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px">{{ __("webapp.loading") }}</div>';

        forwardGeocode(q, function(coords, fullAddress, city) {
            placePvzPin(coords);
            document.getElementById('pvzAddressSearch').value = fullAddress;
            flashInput(document.getElementById('pvzAddressSearch'));
            if (currentPvzProvider === 'yandex') {
                loadYandexPvz(coords[0], coords[1], city || fullAddress || q);
            } else {
                loadCdekPvz(city || q, coords[0], coords[1]);
            }
        });
    }

    function triggerPvzSearch() {
        var q = document.getElementById('pvzAddressSearch').value.trim();
        searchPvzAddress(q);
    }

    document.getElementById('pvzAddressSearch')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            triggerPvzSearch();
        }
    });

    function extractCityName(fullAddress) {
        // "Россия, Москва, ул. Тверская, 1" → "Москва"
        // "Россия, Московская область, Балашиха, ..." → "Балашиха"
        var parts = (fullAddress || '').split(',').map(function(s) { return s.trim(); });
        // Skip "Россия" and region-like parts
        for (var i = 0; i < parts.length; i++) {
            var p = parts[i];
            if (/^(Россия|Russia)$/i.test(p)) continue;
            if (/область|край|округ|республика|region|oblast/i.test(p)) continue;
            if (/район/i.test(p)) continue;
            // First meaningful part is likely the city
            if (p.length > 1 && !/^(ул|пр|пер|наб|ш\.|д\.|стр|корп|бульвар)/i.test(p)) return p;
        }
        return fullAddress;
    }

    /* ========== YANDEX PVZ ========== */
    function loadYandexPvz(lat, lng, city) {
        var listEl = document.getElementById('pvzList');
        listEl.style.display = 'block';
        listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px">{{ __("webapp.loading") }}</div>';

        var params = [];
        if (city) {
            params.push('city=' + encodeURIComponent(city));
            console.log('loadYandexPvz: city=' + city, 'lat=' + lat, 'lng=' + lng);
        }
        if (lat != null && lng != null) {
            params.push('lat=' + lat);
            params.push('lng=' + lng);
        }

        var url = '/api/webapp/delivery/yandex-pvz?' + params.join('&');
        console.log('loadYandexPvz url:', url);

        var providerWhenStarted = currentPvzProvider;

        fetch(url)
            .then(function(r) {
                if (!r.ok) {
                    return r.text().then(function(text) {
                        throw new Error(text || 'Yandex PVZ API error');
                    });
                }
                return r.json();
            })
            .then(function(data) {
                // Пока запрос шёл, пользователь мог переключить провайдера — игнорируем результат
                if (currentPvzProvider !== providerWhenStarted) {
                    console.log('loadYandexPvz: provider changed to', currentPvzProvider, '— abort');
                    return;
                }

                console.log('Yandex PVZ response', data);
                var list = data.pvz || data;
                if (!Array.isArray(list) || !list.length) {
                    listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px">{{ __("webapp.pvz_not_found") }}</div>';
                    return;
                }

                // Clear old markers
                pvzMarkers.forEach(function(m) { if (mapPvz) mapPvz.geoObjects.remove(m); });
                pvzMarkers = [];

                // Add markers on map
                if (mapPvz) {
                    list.forEach(function(pvz) {
                        if (!pvz.lat || !pvz.lng) return;
                        var pm = new ymaps.Placemark([parseFloat(pvz.lat), parseFloat(pvz.lng)], {
                            balloonContentHeader: pvz.name || 'Яндекс ПВЗ',
                            balloonContentBody: (pvz.address || '') + (pvz.instruction ? '<br>' + pvz.instruction : ''),
                            hintContent: pvz.name || pvz.address
                        }, {preset:'islands#yellowCircleDotIcon'});

                        pm.events.add('click', function() {
                            selectYandexPvzByData(pvz.id, pvz.name, pvz.address);
                            document.querySelectorAll('.pvz-item').forEach(function(p) {
                                p.classList.toggle('selected', p.dataset.code === pvz.id);
                            });
                        });
                        mapPvz.geoObjects.add(pm);
                        pvzMarkers.push(pm);
                    });

                    if (pvzMarkers.length > 1) {
                        var bounds = mapPvz.geoObjects.getBounds();
                        if (bounds) mapPvz.setBounds(bounds, {checkZoomRange:true, duration:300});
                    } else if (list[0] && list[0].lat) {
                        mapPvz.setCenter([parseFloat(list[0].lat), parseFloat(list[0].lng)], 12, {duration:300});
                    }
                }

                listEl.innerHTML = list.map(function(pvz) {
                    var esc = function(s){ return (s||'').replace(/'/g, "\\'").replace(/"/g, '&quot;'); };
                    var payMethods = (pvz.payment_methods || []).join(', ');
                    return '<div class="pvz-item" data-code="' + esc(pvz.id) + '"' +
                        ' onclick="selectYandexPvz(this,\'' + esc(pvz.id) + '\',\'' + esc(pvz.name) + '\',\'' + esc(pvz.address) + '\',' + (pvz.lat||0) + ',' + (pvz.lng||0) + ')">' +
                        '<div class="pvz-item__name">' + (pvz.name||'Яндекс ПВЗ') + '</div>' +
                        '<div class="pvz-item__address">' + (pvz.address||'') + '</div>' +
                        (pvz.phone ? '<div class="pvz-item__time">' + pvz.phone + '</div>' : '') +
                        '</div>';
                }).join('');
            })
            .catch(function(err) {
                console.error('loadYandexPvz error:', err);
                listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px">{{ __("webapp.error_loading") }}</div>';
            });
    }

    function selectYandexPvz(el, id, name, address, lat, lng) {
        document.querySelectorAll('.pvz-item').forEach(function(p){ p.classList.remove('selected'); });
        el.classList.add('selected');
        selectYandexPvzByData(id, name, address);
        if (mapPvz && lat && lng) mapPvz.setCenter([parseFloat(lat), parseFloat(lng)], 15, {duration:300});
    }

    function selectYandexPvzByData(id, name, address) {
        selectedPvz = {code:id, name:name, address:address};
        showPvzSheet(name, address, '');
        // Для Яндекс ПВЗ примерный срок доставки 1-3 дня
        var dateRange = formatDeliveryRange(1, 3);
        document.getElementById('pvzSheetDelivery').innerHTML =
            '🚚 Доставим: <b>' + dateRange + '</b> <span style="opacity:.7">· стоимость по тарифу Яндекс</span>';
        document.getElementById('pvzSheetCost').textContent = '';
        deliveryPrice = 0;
        tg.HapticFeedback.impactOccurred("light");
    }

    function loadCdekPvz(city, lat, lng) {
        var listEl = document.getElementById('pvzList');
        listEl.style.display = 'block';
        listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px">{{ __("webapp.loading") }}</div>';

        var cityName = city || '';
        // If it looks like a full address, try to extract city
        if (cityName.indexOf(',') !== -1) cityName = extractCityName(cityName);
        var url = '/api/webapp/delivery/cdek-pvz?city=' + encodeURIComponent(cityName);
        if (lat && lng) url += '&lat=' + lat + '&lng=' + lng;

        console.log('loadCdekPvz: city=' + cityName, 'lat=' + lat, 'lng=' + lng);

        var providerWhenStarted = currentPvzProvider;

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(list) {
                // Пока запрос шёл, пользователь мог переключить провайдера — игнорируем результат
                if (currentPvzProvider !== providerWhenStarted) {
                    console.log('loadCdekPvz: provider changed to', currentPvzProvider, '— abort');
                    return;
                }

                if (!Array.isArray(list) || !list.length) {
                    listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px">{{ __("webapp.pvz_not_found") }}</div>';
                    return;
                }

                // Clear old markers
                pvzMarkers.forEach(function(m) { if (mapPvz) mapPvz.geoObjects.remove(m); });
                pvzMarkers = [];

                // Add markers on map
                if (mapPvz) {
                    list.forEach(function(pvz) {
                        if (!pvz.lat || !pvz.lng) return;
                        var pm = new ymaps.Placemark([parseFloat(pvz.lat), parseFloat(pvz.lng)], {
                            balloonContentHeader: pvz.name || 'ПВЗ',
                            balloonContentBody: (pvz.address || '') + '<br>' + (pvz.work_time || ''),
                            hintContent: pvz.name || pvz.address
                        }, {preset:'islands#greenCircleDotIcon'});

                        pm.events.add('click', function() {
                            selectCdekPvzByData(pvz.code, pvz.name, pvz.address, pvz.city_code);
                            document.querySelectorAll('.pvz-item').forEach(function(p) {
                                p.classList.toggle('selected', p.dataset.code === pvz.code);
                            });
                        });
                        mapPvz.geoObjects.add(pm);
                        pvzMarkers.push(pm);
                    });

                    // Fit map to show all PVZ markers
                    if (pvzMarkers.length > 1) {
                        var bounds = mapPvz.geoObjects.getBounds();
                        if (bounds) mapPvz.setBounds(bounds, {checkZoomRange:true, duration:300});
                    } else if (list[0] && list[0].lat) {
                        mapPvz.setCenter([parseFloat(list[0].lat), parseFloat(list[0].lng)], 12, {duration:300});
                    }
                }

                listEl.innerHTML = list.map(function(pvz) {
                    var esc = function(s){ return (s||'').replace(/'/g, "\\'").replace(/"/g, '&quot;'); };
                    return '<div class="pvz-item" data-code="' + esc(pvz.code) + '"' +
                        ' onclick="selectCdekPvz(this,\'' + esc(pvz.code) + '\',\'' + esc(pvz.name) + '\',\'' + esc(pvz.address) + '\',' + (pvz.lat||0) + ',' + (pvz.lng||0) + ',' + (pvz.city_code||0) + ')">' +
                        '<div class="pvz-item__name">' + (pvz.name||'ПВЗ') + '</div>' +
                        '<div class="pvz-item__address">' + (pvz.address||'') + '</div>' +
                        '<div class="pvz-item__time">' + (pvz.work_time||'') + '</div>' +
                        '</div>';
                }).join('');
            })
            .catch(function() {
                listEl.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:13px">Ошибка загрузки</div>';
            });
    }

    function selectCdekPvz(el, code, name, address, lat, lng, cityCode) {
        document.querySelectorAll('.pvz-item').forEach(function(p){ p.classList.remove('selected'); });
        el.classList.add('selected');
        selectCdekPvzByData(code, name, address, cityCode);
        if (mapPvz && lat && lng) mapPvz.setCenter([parseFloat(lat), parseFloat(lng)], 15, {duration:300});
    }

    function selectCdekPvzByData(code, name, address, cityCode) {
        selectedPvz = {code:code, name:name, address:address};
        showPvzSheet(name, address, '{{ __("webapp.loading") }}');
        document.getElementById('pvzSheetDelivery').textContent = '';

        // Передаём city_code (если знаем) и pvz_code (фолбэк, если city_code потерялся)
        var body = { pvz_code: code };
        if (cityCode && Number(cityCode) > 0) body.to_city_code = Number(cityCode);

        fetch('/api/webapp/delivery/cdek-calculate', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
            body:JSON.stringify(body)
        })
        .then(function(r){ return r.json(); })
        .then(function(tariffs) {
            if (Array.isArray(tariffs) && tariffs.length > 0) {
                // tariffs отсортированы по цене ↑ — берём самый дешёвый (склад-склад)
                var t = tariffs[0];
                deliveryPrice = t.price;

                var deliveryEl = document.getElementById('pvzSheetDelivery');
                var costEl = document.getElementById('pvzSheetCost');

                if (t.min_days != null && t.max_days != null) {
                    var dateRange = formatDeliveryRange(t.min_days, t.max_days);
                    deliveryEl.innerHTML = '🚚 Доставим: <b>' + dateRange + '</b> · <b>~' + Math.round(deliveryPrice) + ' ₽</b>';
                    costEl.textContent = '';
                } else {
                    deliveryEl.textContent = '';
                    costEl.textContent = '~' + Math.round(deliveryPrice) + ' ₽ (' + "{{ __('webapp.approximate_cost') }}" + ')';
                }
            } else {
                deliveryPrice = 0;
                document.getElementById('pvzSheetDelivery').innerHTML =
                    '<span style="opacity:.7">🚚 Стоимость уточнит менеджер</span>';
                document.getElementById('pvzSheetCost').textContent = '';
            }
        })
        .catch(function() {
            deliveryPrice = 0;
            document.getElementById('pvzSheetDelivery').innerHTML =
                '<span style="opacity:.7">🚚 Стоимость уточнит менеджер</span>';
            document.getElementById('pvzSheetCost').textContent = '';
        });
        tg.HapticFeedback.impactOccurred("light");
    }

    /* ========== COURIER ADDRESS SEARCH ========== */
    function searchCourierAddress(q) {
        if (!q || q.length < 2) return;
        console.log('[searchCourierAddress]', q);
        forwardGeocode(q, function(coords, fullAddress, city) {
            if (!isMoscow(coords)) {
                showError("{{ __('webapp.courier_moscow_only') }}");
                tg.HapticFeedback.notificationOccurred("error");
                return;
            }
            lastCourierCoords = coords;
            placeCourierPin(coords);
            var inp = document.getElementById('inp_courier_address');
            inp.value = fullAddress;
            flashInput(inp);
            showCourierSheet(fullAddress);
        });
    }

    function triggerCourierSearch() {
        var q = document.getElementById('inp_courier_address').value.trim();
        searchCourierAddress(q);
    }

    document.getElementById('inp_courier_address')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            triggerCourierSearch();
        }
    });

    /* ========== POPULATE REVIEW ========== */
    function populateReview() {
        var fn = document.getElementById('inp_first_name').value.trim();
        var ln = document.getElementById('inp_last_name').value.trim();
        var pt = document.getElementById('inp_patronymic').value.trim();
        document.getElementById('rv_fio').textContent = [ln, fn, pt].filter(Boolean).join(' ');
        document.getElementById('rv_phone').textContent = document.getElementById('inp_phone').value.trim();
        document.getElementById('rv_email').textContent = document.getElementById('inp_email').value.trim();

        var method = getDeliveryMethod();
        var methodNames = {
            cdek_pvz: '{{ __("webapp.cdek_pvz") }}',
            yandex_pvz: '{{ __("webapp.yandex_pvz") }}',
            courier: '{{ __("webapp.delivery_courier") }}'
        };
        document.getElementById('rv_delivery_method').textContent = methodNames[method] || '—';

        var addr = '—';
        if (method === 'cdek_pvz' && selectedPvz) {
            addr = selectedPvz.address;
        } else if (method === 'yandex_pvz') {
            addr = (selectedPvz && selectedPvz.address) || document.getElementById('pvzAddressSearch').value.trim() || '—';
        } else if (method === 'courier') {
            addr = document.getElementById('inp_courier_address').value.trim();
            var apt = document.getElementById('inp_apartment').value.trim();
            if (apt) addr += ', кв. ' + apt;
        }
        document.getElementById('rv_delivery_address').textContent = addr;

        // Для курьера цена точная (400 или Бесплатно), для ТК — ориентировочная (~)
        var costPrefix = method === 'courier' ? '' : '~';
        var costText;
        if (method === 'courier' && deliveryPrice === 0 && deliveryConfirmed) {
            costText = 'Бесплатно';
        } else if (deliveryPrice > 0) {
            costText = costPrefix + Math.round(deliveryPrice) + ' ₽';
        } else if (method === 'yandex_pvz') {
            costText = 'Рассчитывается Яндексом';
        } else {
            costText = '—';
        }
        document.getElementById('rv_delivery_cost').textContent = costText;

        var totalText;
        if (method === 'courier' && deliveryPrice === 0 && deliveryConfirmed) {
            totalText = 'Бесплатно';
        } else if (deliveryPrice > 0) {
            totalText = costPrefix + Math.round(deliveryPrice) + ' ₽';
        } else if (method === 'yandex_pvz') {
            totalText = 'По тарифу Яндекс';
        } else {
            totalText = '0 ₽';
        }
        document.getElementById('rv_delivery_total').textContent = totalText;

        var grand = pricingTotal + deliveryPrice;
        document.getElementById('rv_grand_total').textContent =
            Math.round(grand).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' ₽';

        {{--document.getElementById('rv_payment').textContent = selectedPayment === 'payme' ? 'PayMe' : "{{ __('webapp.cash') }}";--}}

        var comment = document.getElementById('inp_comment').value.trim();
        document.getElementById('rv_comment_section').style.display = comment ? 'block' : 'none';
        if (comment) document.getElementById('rv_comment_text').textContent = comment;
    }

    /* ========== PLACE ORDER ========== */
    function placeOrder() {
        var btn = document.getElementById('btnNext');
        btn.disabled = true;
        btn.textContent = '...';

        var method = getDeliveryMethod();
        var deliveryAddress = '';

        if (method === 'cdek_pvz' && selectedPvz) {
            deliveryAddress = selectedPvz.name + ' — ' + selectedPvz.address;
        } else if (method === 'yandex_pvz') {
            deliveryAddress = (selectedPvz && selectedPvz.address) || document.getElementById('pvzAddressSearch').value.trim();
        } else if (method === 'courier') {
            deliveryAddress = document.getElementById('inp_courier_address').value.trim();
            var apt = document.getElementById('inp_apartment').value.trim();
            if (apt) deliveryAddress += ', кв. ' + apt;
        }

        var body = {
            chat_id: userId,
            payment_type: selectedPayment,
            delivery_type: 'delivery',
            delivery_address: deliveryAddress,
            delivery_phone: document.getElementById('inp_phone').value.trim(),
            first_name: document.getElementById('inp_first_name').value.trim(),
            last_name: document.getElementById('inp_last_name').value.trim(),
            patronymic: document.getElementById('inp_patronymic').value.trim(),
            email: document.getElementById('inp_email').value.trim(),
            phone: document.getElementById('inp_phone').value.trim(),
            comment: document.getElementById('inp_comment').value.trim(),
            delivery_method: method,
            delivery_pvz_code: selectedPvz ? selectedPvz.code : null,
            delivery_pvz_name: selectedPvz ? selectedPvz.name : null,
            delivery_price: deliveryPrice,
            delivery_city: document.getElementById('pvzAddressSearch').value.trim(),
            delivery_apartment: (document.getElementById('inp_apartment') || {}).value || '',
            delivery_floor: (document.getElementById('inp_floor') || {}).value || '',
            delivery_entrance: (document.getElementById('inp_entrance') || {}).value || '',
            delivery_intercom: (document.getElementById('inp_intercom') || {}).value || '',
            delivery_date: (document.getElementById('inp_delivery_date') || {}).value || null,
        };

        fetch("/api/webapp/order/create", {
            method:"POST",
            headers:{"Content-Type":"application/json","X-CSRF-TOKEN":csrf},
            body:JSON.stringify(body)
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (selectedPayment === 'payme') {
                    var amt = Math.round(data.order_total_price);
                    var callback = "{{ env('APP_URL') }}/telegram/webapp?chat_id=" + userId;
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'https://checkout.paycom.uz';
                    form.innerHTML =
                        '<input type="hidden" name="merchant" value="">' +
                        '<input type="hidden" name="account[order_id]" value="' + data.order_id + '">' +
                        '<input type="hidden" name="amount" value="' + (amt * 100) + '">' +
                        '<input type="hidden" name="lang" value="{{ app()->getLocale() }}">' +
                        '<input type="hidden" name="callback" value="' + callback + '">';
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    goToStep(5);
                    tg.HapticFeedback.notificationOccurred("success");
                }
            } else {
                showError(data.message || data.msg || 'Error');
                btn.disabled = false;
                btn.textContent = "{{ __('webapp.place_order') }}";
            }
        })
        .catch(function() {
            showError('Network error');
            btn.disabled = false;
            btn.textContent = "{{ __('webapp.place_order') }}";
        });
    }

    function showError(message) {
        var box = document.getElementById('alert-box');
        if (box) {
            box.innerText = message;
            box.classList.add('show');
            tg.HapticFeedback.notificationOccurred("error");
            setTimeout(function(){ box.classList.remove('show'); }, 2500);
        }
    }

    // Remove error styling on input
    document.querySelectorAll('.form-group__input').forEach(function(inp) {
        inp.addEventListener('input', function(){ this.classList.remove('error'); });
    });
</script>
@endsection
