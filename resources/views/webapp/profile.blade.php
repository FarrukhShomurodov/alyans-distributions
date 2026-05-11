@extends('webapp.layout')

@section('title', __('webapp.profile_title'))

@section('content')
    {{-- PAGE HEADER --}}
    <div class="page-header">
        <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}"
           class="page-header__back"><i data-lucide="arrow-left"></i></a>
        <div class="page-header__title">{{ __('webapp.profile_title') }}</div>
    </div>

    {{-- PROFILE HERO --}}
    <div class="profile-hero">
        <img id="avatar-img" class="profile-hero__avatar" src="/img/user-placeholder.jpg" alt="avatar">
        <div class="profile-hero__name">{{ $user->first_name }} {{ $user->second_name }}</div>
        @if($user->phone)
            <div class="profile-hero__phone">{{ $user->phone }}</div>
        @endif
    </div>

    {{-- PROFILE MENU --}}
    <div class="profile-menu">
{{--        <a href="#" class="profile-menu__item">--}}
{{--            <div class="profile-menu__icon profile-menu__icon--green"><i data-lucide="user-plus"></i></div>--}}
{{--            <span class="profile-menu__text">{{ __('webapp.promo_referral') }}</span>--}}
{{--            <span class="profile-menu__chevron"><i data-lucide="chevron-right"></i></span>--}}
{{--        </a>--}}

        <div class="profile-menu__item js-orders-toggle" style="cursor:pointer">
            <div class="profile-menu__icon profile-menu__icon--orange"><i data-lucide="receipt-text"></i></div>
            <span class="profile-menu__text">{{ __('webapp.order_history') }}</span>
            <span class="profile-menu__chevron"><i data-lucide="chevron-right"></i></span>
        </div>

        @php $supportManagers = config('services.support.managers', []); @endphp
        @foreach($supportManagers as $managerUname)
            <a href="#" class="profile-menu__item js-contact-support-link" data-uname="{{ $managerUname }}">
                <div class="profile-menu__icon profile-menu__icon--yellow"><i data-lucide="message-circle"></i></div>
                <div>
                    <span class="profile-menu__text">{{ __('webapp.contact_support') }}</span>
                    <div class="profile-menu__subtitle">{{ '@' . $managerUname }}</div>
                </div>
                <span class="profile-menu__chevron"><i data-lucide="chevron-right"></i></span>
            </a>
        @endforeach
    </div>

    {{-- ORDERS SECTION (hidden by default) --}}
    <div id="orders-section" style="display:none; padding: 8px 12px;">
        <div class="section-title" style="padding-left:4px">{{ __('webapp.my_orders') }}</div>

        @forelse($orders as $order)
            <div class="order-card js-order">
                <div class="order-card__header">
                    <div style="display:flex;align-items:center;gap:8px;flex:1">
                        <span class="order-card__id">{{ __('webapp.order_number') }} {{ $order->id }}</span>
                        <span class="order-card__status status-{{ $order->status }}">
                            {{ __('webapp.order_status_'.$order->status) }}
                        </span>
                    </div>
                    <span class="order-card__chevron"><i data-lucide="chevron-down"></i></span>
                </div>
                <div class="order-card__summary">
                    {{ number_format($order->total, 0, '.', ' ') }} &#8381; &middot;
                    {{ $order->created_at->format('d.m.Y H:i') }}
                </div>
                <div class="order-card__items">
                    @foreach($order->items as $item)
                        <div class="order-card__product">
                            <img class="order-card__product-img"
                                 src="{{ $item->product->images->first()
                                    ? asset('storage/' . $item->product->images->first()->url)
                                    : '/no-image.png' }}" alt="">
                            <span class="order-card__product-name">{{ $item->product->name }}</span>
                            <span class="order-card__product-total">
                                {{ $item->quantity }} &times; {{ number_format($item->price, 0, '.', ' ') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="empty-state" style="padding:30px 0">
                <div class="empty-state__icon"><i data-lucide="shopping-bag"></i></div>
                <div class="empty-state__text">{{ __('webapp.orders_empty') }}</div>
            </div>
        @endforelse
    </div>
@endsection

@section('scripts')
    <script>
        // Avatar from Telegram
        const user = tg.initDataUnsafe?.user;
        if (user?.photo_url) {
            document.getElementById("avatar-img").src = user.photo_url;
        }

        // Toggle orders section
        document.querySelector('.js-orders-toggle').addEventListener('click', function() {
            const section = document.getElementById('orders-section');
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
            const chevron = this.querySelector('.profile-menu__chevron');
            if (chevron) chevron.classList.toggle('open');
        });

        // Toggle individual orders
        document.querySelectorAll('.js-order .order-card__header').forEach(header => {
            header.addEventListener('click', function() {
                this.closest('.order-card').classList.toggle('open');
            });
        });

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

        document.querySelectorAll('.js-contact-support-link').forEach(function(link) {
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
        loadCartCount();
    </script>
@endsection
