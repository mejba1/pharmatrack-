<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromoCodeController extends Controller
{
    /** Promo codes are managed by super admins (or users granted the module). */
    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->canModule('promocodes')), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeManage($request);

        // Targeting pickers load on demand (typeahead), so we never render huge
        // customer/product/country option lists — this scales to 10k+ rows.
        $codes = PromoCode::with('product')->latest()->get();

        $stats = [
            'total'    => $codes->count(),
            'active'   => $codes->where('is_active', true)->count(),
            'redeemed' => (int) $codes->sum('used_count'),
        ];

        return view('promo-codes.index', compact('codes', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $this->validateCode($request);
        PromoCode::create($data);

        return back()->with('success', "Promo code {$data['code']} created.");
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $this->validateCode($request, $promoCode->id);
        $promoCode->update($data);

        return back()->with('success', "Promo code {$promoCode->code} updated.");
    }

    public function toggle(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $this->authorizeManage($request);
        $promoCode->update(['is_active' => ! $promoCode->is_active]);

        return back()->with('success', "Promo code {$promoCode->code} " . ($promoCode->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function destroy(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $this->authorizeManage($request);
        $code = $promoCode->code;
        $promoCode->delete();

        return back()->with('success', "Promo code {$code} removed.");
    }

    /** Return a fresh, unique random code (used by the "Generate" button). */
    public function generate(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        return response()->json(['code' => PromoCode::generateCode((string) $request->query('prefix', ''))]);
    }

    /**
     * Typeahead search for the targeting comboboxes. Returns [{id, text}] so the
     * customer/country/product pickers scale to very large datasets.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $q     = trim((string) $request->query('q', ''));
        $type  = (string) $request->query('type', '');
        $ids   = array_filter(array_map('intval', (array) $request->query('ids', []))); // resolve selected labels
        $limit = 20;

        $rows = match ($type) {
            'customers' => \App\Models\Customer::query()
                ->when($ids, fn ($x) => $x->whereIn('id', $ids))
                ->when(! $ids && $q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('customer_code', 'like', "%{$q}%")))
                ->orderBy('name')->limit($limit)->get()
                ->map(fn ($c) => ['id' => $c->id, 'text' => $c->name . ' (' . $c->customer_code . ')']),

            'countries' => \App\Models\Country::query()
                ->when($ids, fn ($x) => $x->whereIn('id', $ids))
                ->when(! $ids && $q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
                ->orderBy('name')->limit($limit)->get()
                ->map(fn ($c) => ['id' => $c->id, 'text' => trim(($c->flag ? $c->flag . ' ' : '') . $c->name)]),

            'products' => \App\Models\Product::query()
                ->when($ids, fn ($x) => $x->whereIn('id', $ids))
                ->when(! $ids && $q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('prn', 'like', "%{$q}%")))
                ->orderBy('name')->limit($limit)->get()
                ->map(fn ($p) => ['id' => $p->id, 'text' => $p->name . ' (' . $p->prn . ')']),

            default => collect(),
        };

        return response()->json($rows->values());
    }

    /** Generate a batch of unique codes sharing one discount configuration. */
    public function bulkStore(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'count'           => 'required|integer|min:1|max:500',
            'prefix'          => 'nullable|string|max:12',
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
            'customer_ids'    => 'nullable|array',
            'customer_ids.*'  => 'integer|exists:customers,id',
            'country_ids'     => 'nullable|array',
            'country_ids.*'   => 'integer|exists:countries,id',
            'product_ids'     => 'nullable|array',
            'product_ids.*'   => 'integer|exists:products,id',
        ], ['product_id.required_if' => 'Select the product these codes discount.']);

        if ($data['scope'] === 'none')      $data['discount_value'] = 0;
        if ($data['scope'] !== 'product')   $data['product_id'] = null;

        $targets = [];
        foreach (['customer_ids', 'country_ids', 'product_ids'] as $key) {
            $targets[$key] = empty($data[$key]) ? null : json_encode(array_values(array_map('intval', $data[$key])));
        }

        $now = now();
        $used = [];
        $rows = [];
        for ($i = 0; $i < $data['count']; $i++) {
            $code = PromoCode::generateCode($data['prefix'] ?? '', $used);
            $used[] = $code;
            $rows[] = [
                'code'            => $code,
                'description'     => $data['description'] ?? null,
                'scope'           => $data['scope'],
                'discount_type'   => $data['discount_type'],
                'discount_value'  => $data['discount_value'],
                'product_id'      => $data['product_id'],
                'customer_ids'    => $targets['customer_ids'],
                'country_ids'     => $targets['country_ids'],
                'product_ids'     => $targets['product_ids'],
                'min_order_value' => $data['min_order_value'] ?? null,
                'max_discount'    => $data['max_discount'] ?? null,
                'usage_limit'     => $data['usage_limit'] ?? null,
                'used_count'      => 0,
                'starts_at'       => $data['starts_at'] ?? null,
                'ends_at'         => $data['ends_at'] ?? null,
                'is_active'       => true,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }
        PromoCode::insert($rows);

        return back()->with('success', "{$data['count']} promo codes generated.");
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
            'customer_ids'    => 'nullable|array',
            'customer_ids.*'  => 'integer|exists:customers,id',
            'country_ids'     => 'nullable|array',
            'country_ids.*'   => 'integer|exists:countries,id',
            'product_ids'     => 'nullable|array',
            'product_ids.*'   => 'integer|exists:products,id',
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

        // Empty allowlists mean "no restriction" — store NULL, not [].
        foreach (['customer_ids', 'country_ids', 'product_ids'] as $key) {
            $data[$key] = empty($data[$key]) ? null : array_values(array_map('intval', $data[$key]));
        }

        return $data;
    }
}
