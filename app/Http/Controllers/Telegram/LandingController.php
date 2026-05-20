<?php

namespace App\Http\Controllers\Telegram;

use App\Models\BotUser;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\LandingCarousel;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromotionSetting;
use App\Models\SliderProduct;
use App\Models\Attribute;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class LandingController
{
    public function categories(Request $request)
    {
        $promotion = PromotionSetting::query()->first();
        $categories = Category::query()
            ->where('is_active', 1)
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('id')
            ->get();

        $carouselItems = LandingCarousel::query()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Рандомные товары из слайдера (вручную добавленные админом)
        $sliderProducts = SliderProduct::getRandomProducts(10);

        // Все товары с пагинацией (на главной, когда категория не выбрана)
        $products = Product::query()
            ->where('is_active', 1)
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereHas('stock', fn ($q) => $q->where('quantity', '>=', 1))
            ->with(['images', 'category'])
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('webapp.categories', compact(
            'categories', 'carouselItems', 'promotion', 'sliderProducts', 'products'
        ));
    }

    public function categoryProducts(Category $category, Request $request)
    {
        $query = $request->get('query');
        $promotion = PromotionSetting::query()->first();
        $selectedAttributes = $this->normalizeAttributeFilters($request->get('attributes', []));
        $selectedCategoryId = (int) $request->get('category_id', $category->id);

        // Все главные категории для пилюль (такой же расчёт что и на главной странице)
        $mainCategories = Category::query()
            ->where('is_active', 1)
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('id')
            ->get();
        // Если единственная корневая — показываем её детей как «главные категории»
        if ($mainCategories->count() === 1) {
            $root = $mainCategories->first();
            $children = $root->children()->where('is_active', 1)
                ->with('childrenRecursive')
                ->orderBy('id')
                ->get();
            if ($children->count() > 0) {
                $mainCategories = $children;
            }
        }

        $carouselItems = LandingCarousel::query()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $category->load('childrenRecursive');
        $allowedCategoryIds = $category->selfAndDescendantIds();

        if (! $allowedCategoryIds->contains($selectedCategoryId)) {
            $selectedCategoryId = $category->id;
        }

        $filterCategory = $selectedCategoryId === $category->id
            ? $category
            : Category::query()->with('childrenRecursive')->find($selectedCategoryId);

        $categoryIds = $filterCategory?->selfAndDescendantIds() ?? collect([$category->id]);
        $attributes = Attribute::query()
            ->with('values')
            ->whereIn('category_id', $categoryIds)
            ->orderBy('id')
            ->get();

        $productQuery = Product::query()
            ->where('is_active', 1)
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereIn('category_id', $categoryIds)
            ->whereHas('stock', function ($q) {
                $q->where('quantity', '>=', 1);
            });

        $this->applyProductSearch($productQuery, $query);
        $this->applyAttributeFilters($productQuery, $selectedAttributes);

        $categoryProducts = $productQuery->with('images')->orderBy('id')->paginate(20)->appends($request->query());

        return view('webapp.index', [
            'category' => $category,
            'products' => $categoryProducts,
            'query' => $query,
            'carouselItems' => $carouselItems,
            'promotion' => $promotion,
            'attributes' => $attributes,
            'selectedAttributes' => $selectedAttributes,
            'categoryTree' => collect([$category]),
            'selectedCategoryId' => $selectedCategoryId,
            'mainCategories' => $mainCategories,
        ]);
    }

    public function allProducts(Request $request): View
    {
        $query = $request->get('query');
        $promotion = PromotionSetting::query()->first();
        $selectedAttributes = $this->normalizeAttributeFilters($request->get('attributes', []));
        $selectedCategoryId = $request->get('category_id');
        $categoriesTree = Category::query()
            ->where('is_active', 1)
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('id')
            ->get();

        $productQuery = Product::query()
            ->where('is_active', 1)
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereHas('stock', function ($q) {
                $q->where('quantity', '>=', 1);
            });

        $this->applyProductSearch($productQuery, $query);

        if ($selectedCategoryId) {
            $filterCategory = Category::query()->find($selectedCategoryId);
            if ($filterCategory) {
                $categoryIds = $filterCategory->selfAndDescendantIds();
                $productQuery->whereIn('category_id', $categoryIds);

                $attributes = Attribute::query()
                    ->with('values')
                    ->whereIn('category_id', $categoryIds)
                    ->orderBy('id')
                    ->get();
            }
        }

        if (! isset($attributes)) {
            $attributes = Attribute::query()->with('values')->orderBy('id')->get();
        }

        $this->applyAttributeFilters($productQuery, $selectedAttributes);

        $products = $productQuery->with(['images', 'category'])->orderBy('id')->paginate(20)->appends($request->query());

        return view('webapp.products', compact(
            'products',
            'query',
            'promotion',
            'attributes',
            'selectedAttributes',
            'categoriesTree',
            'selectedCategoryId'
        ));
    }

    private function normalizeAttributeFilters(array $filters): array
    {
        $normalized = [];

        foreach ($filters as $attributeId => $values) {
            if (! is_array($values)) {
                continue;
            }

            $values = array_values(array_filter($values, fn($v) => $v !== null && $v !== ''));

            if (! empty($values)) {
                $normalized[$attributeId] = $values;
            }
        }

        return $normalized;
    }

    private function applyAttributeFilters($query, array $filters): void
    {
        foreach ($filters as $attributeId => $values) {
            $query->whereHas('attributes', function ($q) use ($attributeId, $values) {
                $q->where('product_attributes.attribute_id', $attributeId)
                    ->whereIn('product_attributes.value', $values);
            });
        }
    }

    /**
     * Умный поиск — делегируем в ProductSearch helper.
     */
    private function applyProductSearch($query, ?string $search): void
    {
        \App\Support\ProductSearch::apply($query, $search);
    }

    public function carouselIndex(): View
    {
        $categories = Category::query()
            ->orderBy('id')
            ->get();

        $carouselItems = LandingCarousel::query()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.landing-carousel.index', compact('categories', 'carouselItems'));
    }

    public function carouselStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'max:4096'],
            'title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $path = $request->file('image')->store('carousel', 'public');

        LandingCarousel::create([
            'image_path' => $path,
            'title' => $data['title'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('landing.carousel.index')
            ->with('success', 'Слайд добавлен');
    }

    public function carouselUpdate(LandingCarousel $carousel, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $carousel->update([
            'title' => $data['title'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('landing.carousel.index')
            ->with('success', 'Слайд обновлён');
    }

    public function carouselDestroy(LandingCarousel $carousel): RedirectResponse
    {
        if ($carousel->image_path) {
            Storage::disk('public')->delete($carousel->image_path);
        }
        $carousel->delete();

        return redirect()
            ->route('landing.carousel.index')
            ->with('success', 'Слайд удалён');
    }

    public function product(Product $product): View
    {
        $promotion = PromotionSetting::query()->first();

        $product->load(['images', 'category', 'attributes', 'stock']);

        return view('webapp.product', compact('product', 'promotion'));
    }

    public function cart(Request $request): View
    {
        $chatId = $request->query('chat_id');

        $cart = null;

        if ($chatId) {
            $user = BotUser::where('chat_id', $chatId)->first();

            if ($user) {
                $cart = Cart::firstOrCreate(['user_id' => $user->id]);
                $cart->load('items.product.images', 'items.product.category', 'promoCode');
            }
        }

        if (!$cart) {
            $cart = new Cart;
            $cart->setRelation('items', collect());
        }

        $pricing = $cart->pricingSummary();

        return view('webapp.cart', compact('cart', 'pricing'));
    }

    public function checkout(Request $request): View
    {
        $chatId = $request->query('chat_id');
        $cart = null;
        $user = null;

        if ($chatId) {
            $user = BotUser::where('chat_id', $chatId)->first();
            if ($user) {
                $cart = Cart::firstOrCreate(['user_id' => $user->id]);
                $cart->load('items.product.images', 'items.product.category', 'promoCode');
            }
        }

        if (!$cart || $cart->items->count() === 0) {
            return redirect()->route('webapp.cart', ['chat_id' => $chatId]);
        }

        // Touch cart to prevent cart:cleanup from deleting items during checkout
        $cart->touch();

        $pricing = $cart->pricingSummary();

        return view('webapp.checkout', compact('cart', 'pricing', 'user'));
    }

    public function profile(Request $request)
    {
        $chatId = $request->query('chat_id') ?: $request->header('X-CHAT-ID');
        $user = $chatId ? BotUser::where('chat_id', $chatId)->first() : null;

        if (! $user) {
            return redirect()->route('webapp', ['chat_id' => $chatId]);
        }

        $orders = Order::where('user_id', $user->id)
            ->with('items.product.images')
            ->orderBy('id', 'desc')
            ->get();

        return view('webapp.profile', compact('user', 'orders'));
    }

    public function favorites(Request $request)
    {
        $chatId = $request->query('chat_id') ?: $request->header('X-CHAT-ID');
        $user = $chatId ? BotUser::where('chat_id', $chatId)->first() : null;

        if (! $user) {
            return redirect()->route('webapp', ['chat_id' => $chatId]);
        }

        $favorites = Favorite::where('user_id', $user->id)
            ->with('product.images')
            ->get();

        return view('webapp.favorites', compact('favorites', 'user'));
    }
}
