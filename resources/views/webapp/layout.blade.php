<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ALYANS DISTRIBUTIONS' }}</title>
    <link rel="stylesheet" href="/style.css">
    <script src="https://telegram.org/js/telegram-web-app.js?1"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    @yield('head')
</head>
<body>

{{-- SIDEBAR OVERLAY --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>

{{-- SIDEBAR MENU --}}
<nav class="sidebar-menu" id="sidebarMenu">
    <div class="sidebar-menu__header">
        <a class="sidebar-menu__brand" href="{{route('webapp')}}">ALYANS DISTRIBUTIONS</a>
        <button class="sidebar-menu__close" id="closeSidebar"><i data-lucide="x"></i></button>
    </div>
    <ul class="sidebar-menu__list">
        <li class="sidebar-menu__item"><a href="#" onclick="openInfoModal('about'); return false;"><i data-lucide="info" style="width:18px;height:18px;margin-right:10px;vertical-align:-3px;opacity:.6"></i>{{ __('webapp.menu_about') }}</a></li>
        <li class="sidebar-menu__item"><a href="#" onclick="openInfoModal('delivery'); return false;"><i data-lucide="truck" style="width:18px;height:18px;margin-right:10px;vertical-align:-3px;opacity:.6"></i>{{ __('webapp.menu_delivery') }}</a></li>
        <li class="sidebar-menu__item"><a href="#" onclick="openInfoModal('sales'); return false;"><i data-lucide="percent" style="width:18px;height:18px;margin-right:10px;vertical-align:-3px;opacity:.6"></i>{{ __('webapp.menu_sales') }}</a></li>
        <li class="sidebar-menu__item"><a href="#" onclick="openInfoModal('returns'); return false;"><i data-lucide="rotate-ccw" style="width:18px;height:18px;margin-right:10px;vertical-align:-3px;opacity:.6"></i>{{ __('webapp.menu_returns') }}</a></li>
        <li class="sidebar-menu__item"><a href="#"><i data-lucide="ticket" style="width:18px;height:18px;margin-right:10px;vertical-align:-3px;opacity:.6"></i>{{ __('webapp.menu_promo') }}</a></li>
        <li class="sidebar-menu__item"><a href="#"><i data-lucide="refresh-cw" style="width:18px;height:18px;margin-right:10px;vertical-align:-3px;opacity:.6"></i>{{ __('webapp.menu_order_update') }}</a></li>
{{--        <li class="sidebar-menu__item"><a href="#"><i data-lucide="undo-2" style="width:18px;height:18px;margin-right:10px;vertical-align:-3px;opacity:.6"></i>{{ __('webapp.menu_return') }}</a></li>--}}
        @php $supportManagers = config('services.support.managers', []); @endphp
        @foreach($supportManagers as $idx => $managerUname)
            <li class="sidebar-menu__item">
                <a href="https://t.me/{{ $managerUname }}" class="js-sidebar-support-link" data-uname="{{ $managerUname }}">
                    <i data-lucide="headphones" style="width:18px;height:18px;margin-right:10px;vertical-align:-3px;opacity:.6"></i>
                    {{ __('webapp.menu_support') }}
                    <span style="opacity:.5;font-size:12px;margin-left:4px">{{ '@' . $managerUname }}</span>
                </a>
            </li>
        @endforeach
    </ul>
    <div class="sidebar-menu__footer">@alyans_distributions_bot</div>
</nav>

{{-- INFO MODAL --}}
<div class="info-modal-overlay" id="infoModalOverlay" onclick="closeInfoModal()"></div>
<div class="info-modal" id="infoModal">
    <div class="info-modal__header">
        <span class="info-modal__title" id="infoModalTitle"></span>
        <button class="info-modal__close" onclick="closeInfoModal()"><i data-lucide="x"></i></button>
    </div>
    <div class="info-modal__body" id="infoModalBody"></div>
</div>

<div class="wrapper">
    <div id="alert-box" class="alert-box"></div>
    @yield('content')
</div>

{{-- BOTTOM NAVIGATION --}}
@section('nav')
<nav class="bottom-nav">
    <a href="{{ route('webapp') }}" class="bottom-nav__item {{ request()->routeIs('webapp') || request()->routeIs('webapp.category.products') ? 'active' : '' }}" id="menu-home">
        <span class="bottom-nav__icon"><i data-lucide="home"></i></span>
        <span>{{ __('webapp.menu.home') }}</span>
    </a>
    <a href="{{ route('webapp.favorites') }}" class="bottom-nav__item {{ request()->routeIs('webapp.favorites') ? 'active' : '' }}" id="menu-favs">
        <span class="bottom-nav__icon"><i data-lucide="heart"></i></span>
        <span>{{ __('webapp.menu.favorites') }}</span>
    </a>
    <a href="{{ route('webapp.cart') }}" class="bottom-nav__item {{ request()->routeIs('webapp.cart') ? 'active' : '' }}" id="bottom-cart-btn">
        <span class="bottom-nav__icon"><i data-lucide="shopping-cart"></i></span>
        <span id="bottom-cart-badge" class="bottom-nav__badge" style="display:none">0</span>
        <span>{{ __('webapp.menu.cart') }}</span>
    </a>
    <a href="{{ route('webapp.profile') }}" class="bottom-nav__item {{ request()->routeIs('webapp.profile') ? 'active' : '' }}" id="menu-profile">
        <span class="bottom-nav__icon"><i data-lucide="user"></i></span>
        <span>{{ __('webapp.menu.profile') }}</span>
    </a>
</nav>
@show

{{-- UNIVERSAL SCRIPTS --}}
<script>
    const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
    if (tg && typeof tg.expand === 'function') tg.expand();

    // Resolve chat_id из нескольких источников:
    // 1) Telegram WebApp (initDataUnsafe.user.id)
    // 2) URL query параметр ?chat_id=123 (фолбэк — приходит с внешних ссылок)
    // 3) sessionStorage (остался от предыдущего визита)
    const urlChatId = new URLSearchParams(window.location.search).get('chat_id');
    const tgUserId = tg?.initDataUnsafe?.user?.id;
    const userId = tgUserId || urlChatId || sessionStorage.getItem('chat_id') || null;

    console.log('[webapp] chat_id resolution:', {
        tgUserId: tgUserId,
        urlChatId: urlChatId,
        sessionId: sessionStorage.getItem('chat_id'),
        final: userId
    });

    if (!tg || !tg.initDataUnsafe || !tg.initData) {
        // Если пришли не через Telegram — редиректим в бот
        if (!urlChatId) {
            const botUsername = "alyans_distributions_bot";
            const webAppUrl = encodeURIComponent(window.location.href);
            window.location.href = `https://t.me/${botUsername}?start=webapp&startapp=${webAppUrl}`;
        }
    }

    // Inject chat_id into links
    if (userId) {
        const selectors = [
            'a[href*="webapp"]',
            '#menu-home', '#menu-favs', '#menu-profile',
            '#bottom-cart-btn', '#cart-btn'
        ];
        selectors.forEach(sel => {
            document.querySelectorAll(sel).forEach(link => {
                if (!link.href) return;
                const base = link.href.split("?")[0];
                const params = new URLSearchParams(link.href.split("?")[1]);
                params.set("chat_id", userId);
                link.href = base + "?" + params.toString();
            });
        });
    }
</script>

<script>
    if (userId) sessionStorage.setItem('chat_id', String(userId));

    const nativeFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        options.headers = options.headers || {};
        const cid = sessionStorage.getItem('chat_id');
        if (cid && cid !== 'undefined' && cid !== 'null') {
            options.headers['X-CHAT-ID'] = cid;
        }
        return nativeFetch(url, options);
    };
</script>

<script>
    fetch('/api/webapp/check-user')
        .then(r => r.json())
        .then(res => {
            if (!res.active) {
                document.body.innerHTML = `
                <div style="height:100vh;display:flex;align-items:center;justify-content:center;
                    background:var(--bg-primary);color:var(--text-primary);font-family:system-ui;text-align:center;padding:20px;">
                    <div>
                        <div style="font-size:48px;margin-bottom:16px;">&#9940;</div>
                        <h2 style="color:var(--text-primary)">{{ __('webapp.access_denied_title') }}</h2>
                        <p style="margin:12px 0;color:var(--text-secondary);">{{ __('webapp.access_denied_text') }}</p>
                        <button onclick="Telegram.WebApp.close()"
                            style="padding:12px 24px;background:var(--danger);border:none;border-radius:12px;color:#fff;font-size:15px;cursor:pointer;box-shadow:0 4px 14px rgba(239,68,68,0.25);">
                            {{ __('webapp.close') }}
                        </button>
                    </div>
                </div>`;
            }
        });
</script>

{{-- SIDEBAR TOGGLE --}}
<script>
    const sidebarMenu = document.getElementById('sidebarMenu');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const openSidebarBtn = document.getElementById('openSidebar');
    const closeSidebarBtn = document.getElementById('closeSidebar');

    function openSidebar() {
        sidebarMenu.classList.add('active');
        sidebarOverlay.classList.add('active');
    }
    function closeSidebar() {
        sidebarMenu.classList.remove('active');
        sidebarOverlay.classList.remove('active');
    }

    if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
    if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
    sidebarOverlay.addEventListener('click', closeSidebar);

    document.querySelectorAll('.js-sidebar-support-link').forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const uname = this.dataset.uname;
            const tgUrl = 'https://t.me/' + uname;
            if (window.Telegram?.WebApp?.openTelegramLink) {
                window.Telegram.WebApp.openTelegramLink(tgUrl);
            } else {
                window.open(tgUrl, '_blank');
            }
        });
    });
</script>

{{-- Initialize Lucide icons --}}
<script>lucide.createIcons();</script>

{{-- Info modal --}}
<script>
var infoContent = {
    about: {
        title: 'О компании',
        body: '<p>ALYANS DISTRIBUTIONS — оптово-розничный поставщик строительных и отделочных материалов с собственным складом и доставкой по городу и области.</p>' +
              '<p>В нашем каталоге более 5 000 позиций: инструменты, крепёж, сухие смеси, краски, плитка, сантехника, электрика, напольные покрытия и всё необходимое для ремонта и строительства.</p>' +
              '<p>Мы работаем напрямую с производителями и проверенными поставщиками, поэтому держим честные цены и гарантируем качество каждого товара.</p>'
    },
    delivery: {
        title: 'Доставка и оплата',
        body: '<h4>Доставка по городу:</h4>' +
              '<ul>' +
              '<li>До 50 кг — от 500₽</li>' +
              '<li>До 500 кг (Газель) — от 1 500₽</li>' +
              '<li>Манипулятор и грузчики — по запросу</li>' +
              '<li>Бесплатно при заказе от 30 000₽</li>' +
              '</ul>' +
              '<h4>Доставка по области и регионам:</h4>' +
              '<ul>' +
              '<li>Транспортные компании (Деловые линии, ПЭК, СДЭК-грузовой)</li>' +
              '<li>Самовывоз со склада — бесплатно</li>' +
              '</ul>' +
              '<p>Оплата: наличными при получении или безналом по счёту для юр. лиц.</p>' +
              '<p>Крупногабаритные позиции отгружаются после согласования с менеджером.</p>'
    },
    /* OLDDELIVERY_TODELETE: {
        title: 'OLD',
        body: '<h4>Доставка по г. Москве:</h4>' +
              '<ul>' +
              '<li>Стоимость доставки 400\u20BD (в пределах МКАД)</li>' +
              '<li>Бесплатная доставка от 5 000\u20BD (в пределах МКАД)</li>' +
              '<li>Оплата при получении</li>' +
              '</ul>' +
              '<h4>Доставка по всей России:</h4>' +
              '<ul>' +
              '<li>СДЭК / СДЭК курьер — оплата доставки при получении</li>' +
              '<li>Яндекс / 5post — оплата доставки вместе с товаром</li>' +
              '</ul>' +
              '<p>Стоимость доставки рассчитывается индивидуально.</p>' +
              '<p><strong>Отправка товара только после 100% предоплаты.</strong></p>'
    }, */
    sales: {
        title: 'Акции и скидки',
        body: '<h4>Наша система скидок:</h4>' +
              '<div class="info-discount-table">' +
              '<div class="info-discount-row"><span>От 5 000\u20BD</span><span class="info-discount-val">5%</span></div>' +
              '<div class="info-discount-row"><span>От 10 000\u20BD</span><span class="info-discount-val">7%</span></div>' +
              '<div class="info-discount-row"><span>От 15 000\u20BD</span><span class="info-discount-val">10%</span></div>' +
              '<div class="info-discount-row"><span>От 50 000\u20BD</span><span class="info-discount-val">15%</span></div>' +
              '</div>'
    },
    returns: {
        title: 'Некачественный товар',
        body: '<p>Если у вас возникли проблемы со строительным товаром \u2014 не переживайте, мы поможем!</p>' +
              '<h4>1. Связь с нами</h4>' +
              '<p>\uD83D\uDCAC Свяжитесь в Телеграм с менеджером, который оформлял Ваш заказ.</p>' +
              '<h4>2. Устранение ложных неисправностей</h4>' +
              '<p>Следуйте рекомендациям менеджера по устранению ложных неисправностей. Менеджер также вправе запросить видео/фото подтверждение брака.</p>' +
              '<h4>3. Экспертиза товара</h4>' +
              '<p><strong>\uD83D\uDEAA Заказ получен курьером:</strong><br>Курьер заберёт устройство на экспертизу. Подробнее в пункте 4.</p>' +
              '<p><strong>\uD83D\uDCE6 Заказ получен посылкой (СДЭК / Яндекс / 5Post):</strong><br>Вы высылаете товар на экспертизу через СДЭК. Если экспертиза выявит исправность устройства \u2014 расходы на доставку полностью оплачивает покупатель. В случае подтверждения брака пересылка за счёт компании (подробнее в пункте 4).</p>' +
              '<h4>4. Экспертиза подтвердила брак</h4>' +
              '<p><strong>\uD83D\uDCE6 Заказ получен посылкой (СДЭК / Яндекс / 5Post):</strong></p>' +
              '<ul><li>Скидка на следующий заказ</li><li>Доотправка брака при следующем заказе</li></ul>' +
              '<p><strong>\uD83D\uDEAA Заказ получен курьером:</strong></p>' +
              '<ul><li>Доотправка брака при следующем заказе</li><li>Замена возможна только на ту же линейку; при отсутствии аналога на складе \u2014 замена на аналогичный товар на выбор</li></ul>' +
              '<h4>5. Сроки обращения</h4>' +
              '<p>\u26A0\uFE0F Мы рекомендуем сразу проверять товары на исправность. По истечении <strong>5 дней</strong> с момента получения заказа мы не сможем подтвердить брак.</p>'
    }
};

function openInfoModal(key) {
    var data = infoContent[key];
    if (!data) return;
    document.getElementById('infoModalTitle').textContent = data.title;
    document.getElementById('infoModalBody').innerHTML = data.body;
    document.getElementById('infoModal').classList.add('active');
    document.getElementById('infoModalOverlay').classList.add('active');
    closeSidebar();
    lucide.createIcons();
}
function closeInfoModal() {
    document.getElementById('infoModal').classList.remove('active');
    document.getElementById('infoModalOverlay').classList.remove('active');
}
</script>

{{-- Add to cart from product tile --}}
<script>
function addToCartFromTile(btn, productId) {
    if (btn.classList.contains('added')) return;
    btn.disabled = true;
    fetch("/api/webapp/cart/add", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name=csrf-token]')?.content || '' },
        body: JSON.stringify({ product_id: productId, chat_id: userId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            btn.classList.add('added');
            btn.innerHTML = '<i data-lucide="check"></i>';
            lucide.createIcons();
            if (typeof tg !== 'undefined') tg.HapticFeedback.notificationOccurred("success");
            // Update cart badge
            var badge = document.getElementById('bottom-cart-badge');
            if (badge) {
                var c = parseInt(badge.innerText || '0') + 1;
                badge.innerText = c;
                badge.style.display = 'block';
            }
            setTimeout(function() {
                btn.classList.remove('added');
                btn.innerHTML = '<i data-lucide="shopping-cart"></i>';
                lucide.createIcons();
                btn.disabled = false;
            }, 1500);
        } else {
            btn.disabled = false;
        }
    })
    .catch(function() { btn.disabled = false; });
}
</script>

@yield('scripts')

</body>
</html>
