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
        title: 'Оплата и оформление',
        body: '<h4>Как оформить заказ:</h4>' +
              '<ul>' +
              '<li>Добавьте товары в корзину</li>' +
              '<li>Перейдите в корзину и нажмите «Оформить заказ»</li>' +
              '<li>Укажите контактные данные</li>' +
              '<li>Подтвердите заказ — менеджер свяжется с вами для уточнения деталей</li>' +
              '</ul>' +
              '<h4>Оплата:</h4>' +
              '<ul>' +
              '<li>Наличными при получении</li>' +
              '<li>Безналом по счёту для юридических лиц</li>' +
              '</ul>' +
              '<p>Все детали (комплектация, сроки, способ передачи товара) согласовываются с менеджером после оформления заказа.</p>'
    },
    sales: {
        title: 'Акции и скидки',
        body: '<h4>Наша система скидок:</h4>' +
              '<div class="info-discount-table">' +
              '<div class="info-discount-row"><span>От 5 000 сум</span><span class="info-discount-val">5%</span></div>' +
              '<div class="info-discount-row"><span>От 10 000 сум</span><span class="info-discount-val">7%</span></div>' +
              '<div class="info-discount-row"><span>От 15 000 сум</span><span class="info-discount-val">10%</span></div>' +
              '<div class="info-discount-row"><span>От 50 000 сум</span><span class="info-discount-val">15%</span></div>' +
              '</div>'
    },
    returns: {
        title: 'Возврат и обмен',
        body: '<p>Если товар оказался некачественным или не подошёл — мы поможем решить вопрос.</p>' +
              '<h4>1. Связь с менеджером</h4>' +
              '<p>💬 Напишите менеджеру, который оформлял ваш заказ. По возможности приложите фото/видео дефекта и номер заказа.</p>' +
              '<h4>2. Проверка товара</h4>' +
              '<p>Менеджер уточнит детали и согласует время осмотра товара на складе или по адресу получения.</p>' +
              '<h4>3. Замена или возврат</h4>' +
              '<ul>' +
              '<li>Замена на аналогичный товар на выбор</li>' +
              '<li>Доукомплектация недостающих позиций при следующем заказе</li>' +
              '<li>Возврат денежных средств на счёт оплаты</li>' +
              '</ul>' +
              '<h4>4. Сроки обращения</h4>' +
              '<p>⚠️ Рекомендуем проверять товар при получении. По истечении <strong>5 дней</strong> с момента получения подтвердить брак сложнее.</p>'
    },
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
