<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromoCodeController extends Controller
{
    public function index(): View
    {
        $codes = PromoCode::with('product')->latest()->get();
        $products = Product::orderBy('name')->get(['id', 'name', 'prn']);

        $stats = [
            'total'    => $codes->count(),
            'active'   => $codes->where('is_active', true)->count(),
            'redeemed' => (int) $codes->sum('used_count'),
        ];

        return view('promo-codes.index', compact('codes', 'products', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCode($request);
        PromoCode::create($data);

        return back()->with('success', "Promo code {$data['code']} created.");
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $data = $this->validateCode($request, $promoCode->id);
        $promoCode->update($data);

        return back()->with('success', "Promo code {$promoCode->code} updated.");
    }

    public function toggle(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update(['is_active' => ! $promoCode->is_active]);

        return back()->with('success', "Promo code {$promoCode->code} " . ($promoCode->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $code = $promoCode->code;
        $promoCode->delete();

        return back()->with('success', "Promo code {$code} removed.");
    }

    private function validateCode(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'code'            => ['required', 'string', 'max:40', Rule::unique('promo_codes', 'code')->ignore($id)->whereNull('deleted_at')],
            'description'     => 'nullable|string|max:200',
            'scope'           => 'required|in:total,product,none',
            'discount_type'   => 'required|in:percent,fixed',
            'discount_value'  => 'required|numeric|min:0',
            'product_id'      => 'nullable|exists:products,id|required_if:scope,product',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount'    => 'nullable|numeric|min:0',
            'usage_limit'     => 'nullable|integer|min:1',
            'starts_at'       => 'nullable|date',
            'ends_at'         => 'nullable|date|after_or_equal:starts_at',
        ], [
            'product_id.required_if' => 'Select the product this code discounts.',
        ]);

        // A "none" code carries no monetary discount.
        if ($data['scope'] === 'none') {
            $data['discount_value'] = 0;
        }
        if ($data['scope'] !== 'product') {
            $data['product_id'] = null;
        }
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
