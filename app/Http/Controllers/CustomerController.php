<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchUnit;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerSale;
use App\Models\CustomerSaleItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomerController extends Controller
{
    // ── Customer list ──────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $filters = [
            'search'     => trim((string) $request->query('search', '')),
            'type'       => $request->query('type', ''),
            'status'     => $request->query('status', ''),
            'manager_id' => $request->query('manager_id', ''),
            'sort'       => $request->query('sort', 'newest'),
            'per_page'   => (int) $request->query('per_page', 15),
        ];
        $perPage = in_array($filters['per_page'], [15, 30, 50, 100], true) ? $filters['per_page'] : 15;

        // Per-manager scope: a manager sees only the customers assigned to them.
        $isAdmin = $request->user()->canViewAll('customers');
        $mine    = !$isAdmin;
        $uid     = $request->user()->id;
        $scoped = fn ($q) => $mine ? $q->where('manager_id', $uid) : $q;

        $query = $scoped(Customer::query()->with(['country', 'manager'])
            ->withCount('sales')
            ->withCount('soldUnits as units_sold'));

        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('customer_code', 'like', "%{$s}%")
                ->orWhere('contact_person', 'like', "%{$s}%")
                ->orWhere('contact_email', 'like', "%{$s}%")
                ->orWhere('contact_phone', 'like', "%{$s}%"));
        }
        if ($filters['type'] !== '')   $query->where('type', $filters['type']);
        if ($filters['status'] !== '') $query->where('status', $filters['status']);
        if ($isAdmin && $filters['manager_id'] !== '') {
            $query->where('manager_id', $filters['manager_id'] === 'none' ? null : $filters['manager_id']);
        }

        match ($filters['sort']) {
            'oldest' => $query->orderBy('id'),
            'name'   => $query->orderBy('name'),
            'sales'  => $query->orderByDesc('sales_count'),
            'units'  => $query->orderByDesc('units_sold'),
            default  => $query->orderByDesc('id'),
        };

        $customers = $query->paginate($perPage)->withQueryString();

        $stats = [
            'customers'  => $scoped(Customer::query())->count(),
            'active'     => $scoped(Customer::where('status', 'active'))->count(),
            'sales'      => CustomerSale::when($mine, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('manager_id', $uid)))->count(),
            'units_sold' => BatchUnit::whereNotNull('sold_to_id')
                ->when($mine, fn ($q) => $q->whereHas('soldTo', fn ($c) => $c->where('manager_id', $uid)))->count(),
        ];

        $countries = Country::orderBy('name')->get(['id', 'name', 'flag']);
        $products  = Product::orderBy('name')->get(['id', 'name', 'prn']);
        $batches   = Batch::with('product')->orderByDesc('id')->limit(1000)->get(['id', 'brn', 'batch_number', 'product_id']);
        $types     = Customer::TYPES;
        $managers  = User::orderBy('name')->get(['id', 'name', 'role']);   // for assign dropdown / filter

        $recentSales  = CustomerSale::with('customer')->when($mine, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('manager_id', $uid)))->latest()->limit(8)->get();
        $allCustomers = $scoped(Customer::query())->orderBy('name')->get(['id', 'name', 'customer_code']);

        return view('customers.index', compact('customers', 'stats', 'filters', 'countries', 'products', 'batches', 'types', 'managers', 'isAdmin', 'recentSales', 'allCustomers'));
    }

    // ── Customer CRUD ──────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCustomer($request);
        if (empty($data['password'])) unset($data['password']);
        $data['company_logo']  = $this->handleLogo($request, null);
        $data['customer_code'] = Customer::nextCode();
        $data['status'] ??= 'active';
        // Super admin assigns any manager; a manager creating a customer owns it.
        $data['manager_id'] = $request->user()->seesAllData()
            ? ($data['manager_id'] ?? null)
            : $request->user()->id;

        $c = Customer::create($data);
        return back()->with('success', "Customer '{$c->name}' ({$c->customer_code}) added.");
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validateCustomer($request, $customer->id);
        if (empty($data['password'])) unset($data['password']);
        $data['company_logo'] = $this->handleLogo($request, $customer);
        // Only super admin may (re)assign the account manager.
        if (!$request->user()->seesAllData()) {
            unset($data['manager_id']);
        }
        $customer->update($data);
        return back()->with('success', "Customer '{$customer->name}' updated.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->exists()) {
            return back()->with('error', 'Cannot delete a customer that has sales. Suspend it instead.');
        }
        $name = $customer->name;
        $customer->delete();
        return back()->with('success', "Customer '{$name}' removed.");
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load('country', 'manager');
        $sales = $customer->sales()->with('items.product', 'items.batch')->latest()->limit(50)->get();
        $units = $customer->soldUnits()->with('batch.product')->latest('sold_at')->limit(100)->get();

        return response()->json([
            ...$customer->toArray(),
            'type_label'    => $customer->type_label,
            'id_type_label' => $customer->id_type_label,
            'logo_url'      => $customer->logo_url,
            'has_login'     => ! empty($customer->password) && ! empty($customer->email),
            'country_name'  => $customer->country?->name,
            'manager_name'  => $customer->manager?->name,
            'sales' => $sales->map(fn ($s) => [
                'id'        => $s->id,
                'reference' => $s->reference_number,
                'date'      => $s->sale_date?->format('d M Y'),
                'status'    => $s->status,
                'total'     => $s->total,
                'currency'  => $s->currency,
                'units'     => $s->units_count,
                'items'     => $s->items->map(fn ($i) => [
                    'product' => $i->product?->name,
                    'batch'   => $i->batch?->brn,
                    'qty'     => $i->quantity,
                ]),
            ]),
            'units' => $units->map(fn ($u) => [
                'code'    => $u->secret_code,
                'serial'  => $u->serial_number,
                'product' => $u->batch?->product?->name,
                'batch'   => $u->batch?->brn,
                'sold_at' => $u->sold_at?->format('d M Y'),
            ]),
        ]);
    }

    // ── Record a sale (multi-line + unit assignment) ───────────────────────
    public function storeSale(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'sale_date'          => 'required|date',
            'status'             => 'nullable|in:draft,confirmed,delivered,cancelled',
            'currency'           => 'nullable|string|max:3',
            'notes'              => 'nullable|string|max:2000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id'   => 'nullable|exists:batches,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.uuc_codes'  => 'nullable|string|max:20000',
        ]);

        $result = DB::transaction(function () use ($data) {
            $sale = CustomerSale::create([
                'reference_number' => CustomerSale::nextReference(),
                'customer_id'      => $data['customer_id'],
                'sale_date'        => $data['sale_date'],
                'status'           => $data['status'] ?? 'confirmed',
                'currency'         => strtoupper($data['currency'] ?? 'USD'),
                'notes'            => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            $unitsAssigned = 0;
            $warnings = [];

            foreach ($data['items'] as $i => $item) {
                $price = (float) ($item['unit_price'] ?? 0);

                // Resolve the units to assign for this line.
                $units = $this->resolveUnits($item, $sale->customer_id);
                $qty   = count($units) ?: (int) $item['quantity'];

                foreach ($units as $u) {
                    $u->forceFill([
                        'customer_sale_id' => $sale->id,
                        'sold_to_id'       => $sale->customer_id,
                        'sold_at'          => now(),
                    ])->save();
                }
                $unitsAssigned += count($units);

                if (!empty($item['batch_id']) && count($units) < (int) $item['quantity']) {
                    $warnings[] = "Line " . ($i + 1) . ": only " . count($units) . " of {$item['quantity']} units were available to assign.";
                }

                CustomerSaleItem::create([
                    'customer_sale_id' => $sale->id,
                    'product_id'       => $item['product_id'],
                    'batch_id'         => $item['batch_id'] ?? null,
                    'quantity'         => $qty,
                    'unit_price'       => $price,
                    'line_total'       => $qty * $price,
                ]);
                $subtotal += $qty * $price;
            }

            $sale->update(['subtotal' => $subtotal, 'total' => $subtotal, 'units_count' => $unitsAssigned]);

            return ['sale' => $sale, 'warnings' => $warnings];
        });

        $msg = "Sale {$result['sale']->reference_number} recorded ({$result['sale']->units_count} units assigned).";
        if ($result['warnings']) {
            return back()->with('success', $msg)->with('warning', implode(' ', $result['warnings']));
        }
        return back()->with('success', $msg);
    }

    public function showSale(CustomerSale $sale): JsonResponse
    {
        $sale->load('customer', 'items.product', 'items.batch', 'units.batch');
        return response()->json([
            'reference' => $sale->reference_number,
            'customer'  => $sale->customer?->name,
            'code'      => $sale->customer?->customer_code,
            'date'      => $sale->sale_date?->format('d M Y'),
            'status'    => $sale->status,
            'currency'  => $sale->currency,
            'total'     => $sale->total,
            'units_count' => $sale->units_count,
            'notes'     => $sale->notes,
            'items'     => $sale->items->map(fn ($i) => [
                'product' => $i->product?->name,
                'batch'   => $i->batch?->brn,
                'qty'     => $i->quantity,
                'price'   => $i->unit_price,
                'total'   => $i->line_total,
            ]),
            'units'     => $sale->units->map(fn ($u) => [
                'code'   => $u->secret_code,
                'serial' => $u->serial_number,
                'batch'  => $u->batch?->brn,
            ]),
        ]);
    }

    // ── Trace: who purchased this unit / reference? ────────────────────────
    public function trace(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $result = null;

        if ($q !== '') {
            // Try a sale reference first, then a UUC / serial.
            $sale = CustomerSale::with('customer', 'items.product', 'units.batch.product')
                ->where('reference_number', $q)->first();

            $unit = $sale ? null : BatchUnit::with('batch.product', 'soldTo', 'sale')
                ->where('secret_code', $q)->orWhere('unique_number', $q)->first();

            $result = ['searched' => $q, 'sale' => $sale, 'unit' => $unit];
        }

        return view('customers.trace', compact('result', 'q'));
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    private function validateCustomer(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name'                  => 'required|string|max:160',
            'type'                  => 'required|in:' . implode(',', array_keys(Customer::TYPES)),
            'email'                 => 'nullable|email|max:160|unique:customers,email,' . ($id ?? 'NULL'),
            'phone'                 => 'nullable|string|max:40',
            'country_id'            => 'required|exists:countries,id',
            'city'                  => 'nullable|string|max:120',
            'address'               => 'nullable|string|max:500',
            'company_name'          => 'nullable|string|max:160',
            'company_id'            => 'nullable|string|max:100',
            'identification_type'   => 'nullable|in:' . implode(',', array_keys(Customer::ID_TYPES)),
            'identification_number' => 'nullable|string|max:100',
            'referenced_by'         => 'nullable|string|max:160',
            'company_logo'          => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'manager_id'            => 'nullable|exists:users,id',
            'license_number'        => 'nullable|string|max:120|unique:customers,license_number,' . ($id ?? 'NULL'),
            'license_expiry'        => 'nullable|date',
            'status'                => 'nullable|in:active,suspended,expired,pending',
            'password'              => 'nullable|string|min:6|max:100',
        ]);
    }

    /**
     * Resolve the actual UUC units to assign for a sale line: explicit codes if
     * given, otherwise the next available (unsold) units from the batch.
     */
    private function resolveUnits(array $item, int $customerId): array
    {
        $codes = $this->splitCodes($item['uuc_codes'] ?? '');

        if ($codes) {
            return BatchUnit::whereNull('customer_sale_id')
                ->where(fn ($q) => $q->whereIn('secret_code', $codes)->orWhereIn('unique_number', $codes))
                ->get()->all();
        }

        if (!empty($item['batch_id'])) {
            return BatchUnit::where('batch_id', $item['batch_id'])
                ->whereNull('customer_sale_id')
                ->orderBy('id')->limit((int) $item['quantity'])->get()->all();
        }

        return [];
    }

    private function splitCodes(string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', trim($raw)))->filter()->unique()->values()->all();
    }

    /** Store an uploaded company logo (replacing any old one); keep current if none. */
    private function handleLogo(Request $request, ?Customer $customer): ?string
    {
        if (! $request->hasFile('company_logo')) {
            return $customer?->company_logo;
        }

        if ($customer?->company_logo) {
            Storage::disk('public')->delete($customer->company_logo);
        }

        return $request->file('company_logo')->store('customers/logos', 'public');
    }
}
