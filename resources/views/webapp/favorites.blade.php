@extends('webapp.layout')

@section('title', __('webapp.favorites_title'))

@section('content')
    {{-- PAGE HEADER --}}
    <div class="page-header">
        <a href="{{ route('webapp', ['chat_id' => request('chat_id')]) }}"
           class="page-header__back"><i data-lucide="arrow-left"></i></a>
        <div class="page-header__title">{{ __('webapp.favorites_title') }}</div>
    </div>

    @if($favorites->count())
        <div class="favorites-grid">
            @foreach($favorites as $fav)
                <a href="{{ route('webapp.product.show', $fav->product->id) }}" class="product-tile">
                    <button class="product-tile__fav active js-unfav"
                            data-product="{{ $fav->product->id }}"
                            onclick="event.preventDefault(); event.stopPropagation(); removeFav(this, {{ $fav->product->id }})">
                        <i data-lucide="heart"></i>
                    </button>
                    <img class="product-tile__img" loading="lazy"
                         src="{{ $fav->product->images->first()
                            ? asset('storage/' . $fav->product->images->first()->url)
                            : '/no-image.png' }}"
                         alt="{{ $fav->product->name }}">
                    <div class="product-tile__info">
                        <div class="product-tile__name">{{ $fav->product->name }}</div>
                        <div class="product-tile__bottom">
                            <div class="product-tile__price">
                                {{ number_format($fav->product->price, 0, '.', ' ') }} сум
                            </div>
                            <button class="product-tile__cart" onclick="event.preventDefault(); event.stopPropagation(); addToCartFromTile(this, {{ $fav->product->id }})">
                                <i data-lucide="shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state__icon"><i data-lucide="heart-off"></i></div>
            <div class="empty-state__text">{{ __('webapp.favorites_empty') }}</div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        const csrf = "{{ csrf_token() }}";

        function removeFav(btn, productId) {
            fetch("/api/webapp/favorite/toggle", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
                body: JSON.stringify({ chat_id: userId, product_id: productId })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.favorite) {
                    btn.closest('.product-tile').remove();
                    // Check if grid is now empty
                    const grid = document.querySelector('.favorites-grid');
                    if (grid && !grid.children.length) {
                        grid.outerHTML = `
                            <div class="empty-state">
                                <div class="empty-state__icon"><i data-lucide="heart-off"></i></div>
                                <div class="empty-state__text">{{ __('webapp.favorites_empty') }}</div>
                            </div>`;
                        lucide.createIcons();
                    }
                }
                tg.HapticFeedback.impactOccurred("light");
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
