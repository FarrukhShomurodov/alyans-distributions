<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\PromoCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PromoCodeController
{
    public function index(): View
    {
        $promoCodes = PromoCode::orderByDesc('created_at')->paginate(20);

        return view('admin.promo-codes.index', compact('promoCodes'));
    }

    public function create(): View
    {
        return view('admin.promo-codes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:promo_codes,code',
            'description' => 'nullable|string|max:255',
            'type' => ['required', Rule::in([PromoCode::TYPE_PERCENT, PromoCode::TYPE_FIXED])],
            'value' => 'required|integer|min:1',
            'min_order_amount' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:1',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $data['code'] = Str::upper($data['code']);
        $data['is_active'] = $request->has('is_active');
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;

        PromoCode::create($data);

        return redirect()->route('promo-codes.index')->with('success', 'Промокод создан');
    }

    public function edit(PromoCode $promoCode): View
    {
        return view('admin.promo-codes.edit', compact('promoCode'));
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('promo_codes')->ignore($promoCode->id)],
            'description' => 'nullable|string|max:255',
            'type' => ['required', Rule::in([PromoCode::TYPE_PERCENT, PromoCode::TYPE_FIXED])],
            'value' => 'required|integer|min:1',
            'min_order_amount' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:1',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $data['code'] = Str::upper($data['code']);
        $data['is_active'] = $request->has('is_active');
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;

        $promoCode->update($data);

        return redirect()->route('promo-codes.index')->with('success', 'Промокод обновлен');
    }

    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->delete();

        return redirect()->route('promo-codes.index')->with('success', 'Промокод удален');
    }
}
