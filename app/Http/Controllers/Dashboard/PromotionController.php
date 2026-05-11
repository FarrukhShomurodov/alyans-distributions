<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\DiscountTier;
use App\Models\PromotionSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController
{
    public function index(): View
    {
        $promotion = PromotionSetting::query()->first();

        if (!$promotion) {
            $promotion = PromotionSetting::create([
                'active_type' => PromotionSetting::TYPE_PERCENT,
                'discount_percent' => null,
            ]);
        }

        $discountTiers = DiscountTier::orderBy('min_amount')->get();

        return view('admin.promotions.index', compact('promotion', 'discountTiers'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'active_type' => ['required', Rule::in([
                PromotionSetting::TYPE_PERCENT,
                PromotionSetting::TYPE_ONE_PLUS_TWO,
            ])],
            'discount_percent' => [
                'nullable',
                Rule::requiredIf(
                    $request->input('active_type') === PromotionSetting::TYPE_PERCENT
                ),
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        if ($data['active_type'] !== PromotionSetting::TYPE_PERCENT) {
            $data['discount_percent'] = null;
        }

        $promotion = PromotionSetting::query()->first();

        if (!$promotion) {
            PromotionSetting::create($data);
        } else {
            $promotion->update($data);
        }

        return redirect()
            ->route('promotions.index')
            ->with('success', 'Акция обновлена');
    }

    public function storeTier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'min_amount' => 'required|integer|min:1|unique:discount_tiers,min_amount',
            'discount_percent' => 'required|integer|min:1|max:100',
        ]);

        DiscountTier::create($data);

        return redirect()
            ->route('promotions.index')
            ->with('success', 'Порог скидки добавлен');
    }

    public function updateTier(Request $request, DiscountTier $tier): RedirectResponse
    {
        $data = $request->validate([
            'min_amount' => ['required', 'integer', 'min:1', Rule::unique('discount_tiers')->ignore($tier->id)],
            'discount_percent' => 'required|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');

        $tier->update($data);

        return redirect()
            ->route('promotions.index')
            ->with('success', 'Порог скидки обновлен');
    }

    public function destroyTier(DiscountTier $tier): RedirectResponse
    {
        $tier->delete();

        return redirect()
            ->route('promotions.index')
            ->with('success', 'Порог скидки удален');
    }
}
