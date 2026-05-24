@php
    $pd = (int) ($product->discount_percent ?? 0);
    $pt = $promotion?->active_type ?? null;
    $pp = (int) ($promotion?->discount_percent ?? 0);
    $fp = $product->price;
    $badge = null;
    if ($pd > 0) {
        $badge = '-'.$pd.'%';
        $fp = $product->price * (100 - $pd) / 100;
    } elseif ($pt === \App\Models\PromotionSetting::TYPE_PERCENT && $pp > 0) {
        $badge = '-'.$pp.'%';
        $fp = $product->price * (100 - $pp) / 100;
    } elseif ($pt === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO) {
        $badge = '1+2';
    }
@endphp
<a href="{{ route('webapp.product.show', $product->id) }}" class="product-tile">
    @if($badge)
        <span class="product-tile__discount">{{ $badge }}</span>
    @endif
    @if(!empty($product->is_top))
        <span class="product-tile__top-badge">⭐ ХИТ</span>
    @endif
    <button class="product-tile__fav js-fav-btn" data-product="{{ $product->id }}"
            onclick="event.preventDefault(); event.stopPropagation(); toggleFav(this, {{ $product->id }})">
        <i data-lucide="heart"></i>
    </button>
    <img class="product-tile__img" loading="lazy"
         src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->url) : '/no-image.png' }}"
         alt="{{ $product->name }}">
    <div class="product-tile__info">
        @if(!empty($product->brand))
            <div class="product-tile__brand">{{ $product->brand }}</div>
        @endif
        <div class="product-tile__name">{{ $product->name }}</div>
        <div class="product-tile__bottom">
            <div class="product-tile__price">
                {{ number_format($fp, 0, '.', ' ') }} сум
                @if(!empty($product->unit))<span class="product-tile__unit">/ {{ $product->unit }}</span>@endif
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
