<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Product;
use App\Models\SliderProduct;
use App\Support\ProductSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SliderProductController
{
    public function index(Request $request): View
    {
        $sliderProducts = SliderProduct::with('product.category')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        // Поиск товаров для добавления
        $search = $request->get('search');
        $searchResults = collect();

        if ($search) {
            $existingIds = $sliderProducts->pluck('product_id')->toArray();

            $query = Product::where('is_active', true);
            ProductSearch::apply($query, $search);

            $searchResults = $query
                ->whereNotIn('id', $existingIds)
                ->with('category')
                ->limit(20)
                ->get();
        }

        return view('admin.slider-products.index', compact('sliderProducts', 'searchResults', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $maxSort = SliderProduct::max('sort_order') ?? 0;

        SliderProduct::firstOrCreate(
            ['product_id' => $data['product_id']],
            ['sort_order' => $maxSort + 1, 'is_active' => true]
        );

        return redirect()->route('slider-products.index')
            ->with('success', 'Товар добавлен в слайдер');
    }

    public function update(Request $request, SliderProduct $sliderProduct): RedirectResponse
    {
        $data = $request->validate([
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $sliderProduct->update([
            'sort_order' => $data['sort_order'] ?? $sliderProduct->sort_order,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('slider-products.index')
            ->with('success', 'Обновлено');
    }

    public function destroy(SliderProduct $sliderProduct): RedirectResponse
    {
        $sliderProduct->delete();

        return redirect()->route('slider-products.index')
            ->with('success', 'Товар удалён из слайдера');
    }

    /**
     * AJAX-поиск товаров для добавления
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $existingIds = SliderProduct::pluck('product_id')->toArray();

        $query = Product::where('is_active', true);
        ProductSearch::apply($query, $search);

        $products = $query
            ->whereNotIn('id', $existingIds)
            ->with('category')
            ->limit(15)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => number_format($p->price, 0, '', ' ') . ' сум',
                'category' => $p->category?->name ?? '—',
            ]);

        return response()->json($products);
    }
}
