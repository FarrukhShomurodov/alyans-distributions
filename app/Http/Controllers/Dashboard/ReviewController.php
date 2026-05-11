<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\BotUser;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReviewController
{
    public function index(Request $request): View
    {
        $reviews = Review::query()
            ->with(['product', 'user'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id)
            )
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id)
            )
            ->when($request->rating, fn ($q) => $q->where('rating', $request->rating)
            )
            ->when($request->text, fn ($q) => $q->where('text', 'like', "%{$request->text}%")
            )
            ->orderByDesc('id')
            ->paginate(20);

        $products = Product::select('id', 'name')->get();
        $users = BotUser::select('id', 'first_name', 'second_name')->get();

        return view('admin.reviews.index', compact('reviews', 'products', 'users'));
    }
}
