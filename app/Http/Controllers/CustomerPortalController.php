<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Support\PortalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Customer self-service portal — login + dashboard, on the `customer` auth
 * guard (separate from the staff `web` guard).
 */
class CustomerPortalController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login', ['portal' => PortalSettings::all()]);
    }

    public function login(Request $request): RedirectResponse
    {
        if (! PortalSettings::get('portal_enabled')) {
            return back()->withErrors(['email' => 'The customer portal is temporarily unavailable. Please try again later.']);
        }

        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('customer')->attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->onlyInput('email');
        }

        $customer = Auth::guard('customer')->user();
        if ($customer->status !== 'active' || ! $customer->canUsePortal()) {
            Auth::guard('customer')->logout();

            return back()->withErrors(['email' => 'Your account cannot access the portal. Please contact us.'])->onlyInput('email');
        }

        $customer->forceFill(['last_login_at' => now()])->saveQuietly();
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function dashboard(): View
    {
        $customer = Auth::guard('customer')->user()->load('country', 'manager');
        $cid = $customer->id;

        $orders = $customer->purchaseOrders()
            ->with('lines.product', 'documents', 'salesOrder.proformaInvoice.commercialInvoices:id,proforma_invoice_id')
            ->latest()->get();
        $units  = $customer->soldUnits()->with('batch.product')->latest('sold_at')->limit(50)->get();

        // Orders as a JS-ready dataset for the client-side data table.
        $ordersData = $orders->map(function ($po) {
            $chain = $po->chainStages();

            return [
                'id'        => $po->id,
                'po_number' => $po->po_number,
                'date'      => $po->po_date?->format('d M Y'),
                'dateISO'   => $po->po_date?->format('Y-m-d'),
                'items'     => $po->lines->count(),
                'step'      => $chain['step'],
                'ci_count'  => $chain['ci_count'],
                'status'    => $po->status_label ?? ucfirst((string) $po->status),
                'currency'  => $po->currency,
                'totalNum'  => (float) $po->total_value,
                'total'     => number_format((float) $po->total_value, 2),
                'editable'  => $po->isEditableByCustomer(),
                'show_url'  => route('portal.order.show', $po),
                'edit_url'  => route('portal.order.edit', $po),
            ];
        })->values();

        // Invoices raised against this customer's sales orders.
        $pis = \App\Models\ProformaInvoice::whereHas('salesOrder', fn ($q) => $q->where('customer_id', $cid))
            ->with('salesOrder.purchaseOrder:id,po_number', 'documents')
            ->latest('pi_date')->get();
        $cis = \App\Models\CommercialInvoice::whereHas('proformaInvoice.salesOrder', fn ($q) => $q->where('customer_id', $cid))
            ->with('proformaInvoice.salesOrder.purchaseOrder:id,po_number', 'documents', 'lines.product', 'payments')
            ->latest('ci_date')->get();

        $invoices = $pis->map(fn ($p) => [
            'type'        => 'Proforma',
            'number'      => $p->pi_number,
            'date'        => $p->pi_date?->format('d M Y'),
            'dateISO'     => $p->pi_date?->format('Y-m-d'),
            'currency'    => $p->currency,
            'totalNum'    => (float) $p->total_value,
            'total'       => number_format((float) $p->total_value, 2),
            'status'      => $p->status_label ?? ucfirst((string) $p->status),
            'reference'   => $p->salesOrder?->purchaseOrder?->po_number,
            'valid_until' => $p->valid_until?->format('d M Y'),
            'subtotal'    => number_format((float) $p->subtotal, 2),
            'freight'     => number_format((float) $p->freight, 2),
            'extra_label' => 'Tax',
            'extra'       => number_format((float) $p->tax_amount, 2),
            'discount'    => number_format(0, 2),
            'paid'        => '—',
            'due'         => '—',
            'payStatus'   => 'n/a',
            'doc_url'     => $p->documents->first()?->url,
            'pdf_url'     => route('portal.invoice.pdf', ['type' => 'pi', 'id' => $p->id]),
        ])->concat($cis->map(fn ($c) => [
            'type'        => 'Commercial',
            'number'      => $c->ci_number,
            'date'        => $c->ci_date?->format('d M Y'),
            'dateISO'     => $c->ci_date?->format('Y-m-d'),
            'currency'    => $c->currency,
            'totalNum'    => (float) ($c->total_value ?? 0),
            'total'       => number_format((float) ($c->total_value ?? 0), 2),
            'status'      => $c->status_label ?? ucfirst((string) $c->status),
            'reference'   => $c->proformaInvoice?->salesOrder?->purchaseOrder?->po_number,
            'valid_until' => null,
            'subtotal'    => number_format((float) $c->subtotal, 2),
            'freight'     => number_format((float) $c->freight, 2),
            'extra_label' => 'Insurance',
            'extra'       => number_format((float) $c->insurance, 2),
            'discount'    => number_format($c->discount_total, 2),
            'paid'        => number_format($c->paid_amount, 2),
            'due'         => number_format($c->due_amount, 2),
            'payStatus'   => $c->status === 'cancelled' ? 'n/a' : $c->payment_status,
            'doc_url'     => $c->documents->first()?->url,
            'pdf_url'     => route('portal.invoice.pdf', ['type' => 'ci', 'id' => $c->id]),
        ]))->values();

        // ── Accounting: financial summary + product-wise breakdown (from CIs) ──
        $liveCis = $cis->where('status', '!=', 'cancelled');
        $currency = $liveCis->first()?->currency ?? 'USD';

        $financials = [
            'currency'  => $currency,
            'invoiced'  => number_format($inv = (float) $liveCis->sum(fn ($c) => $c->payable_amount), 2),
            'paid'      => number_format($paid = (float) $liveCis->sum(fn ($c) => $c->paid_amount), 2),
            'due'       => number_format(max(0, $inv - $paid), 2),
            'discount'  => number_format((float) $liveCis->sum(fn ($c) => $c->discount_total), 2),
            'count'     => $liveCis->count(),
        ];

        $productSummary = $liveCis->flatMap->lines
            ->groupBy('product_id')
            ->map(function ($lines) use ($currency) {
                $first = $lines->first();
                $gross    = (float) $lines->sum('line_total');
                $discount = (float) $lines->sum('discount_amount');
                return [
                    'product'     => $first->product?->name ?? '—',
                    'prn'         => $first->product?->prn ?? '',
                    'qty'         => (int) $lines->sum('quantity'),
                    'currency'    => $currency,
                    'grossNum'    => $gross,
                    'gross'       => number_format($gross, 2),
                    'discountNum' => $discount,
                    'discount'    => number_format($discount, 2),
                    'netNum'      => max(0, $gross - $discount),
                    'net'         => number_format(max(0, $gross - $discount), 2),
                ];
            })->sortByDesc('netNum')->values();

        // Documents staff explicitly shared with this customer.
        $documents = $customer->documents()->with('uploader')->get()->map(fn ($d) => [
            'name' => $d->name, 'category' => $d->category, 'size' => $d->size_human,
            'date' => $d->created_at?->format('d M Y'), 'url' => $d->url, 'icon' => $d->icon_class,
        ])->values();

        $stats = [
            'orders'    => $customer->purchaseOrders()->count(),
            'units'     => $customer->soldUnits()->count(),
            'invoices'  => $pis->count() + $cis->count(),
            'documents' => $documents->count(),
        ];

        $portal = PortalSettings::all();
        // Ordering button respects the per-customer override, not just the global flag.
        $portal['portal_allow_ordering'] = $customer->canPlaceOrders();

        return view('portal.dashboard', compact('customer', 'ordersData', 'units', 'invoices', 'documents', 'stats', 'portal', 'financials', 'productSummary'));
    }

    // ── Invoice PDF download (ownership-checked) ──────────────────────────
    public function invoicePdf(string $type, int $id)
    {
        $customer = Auth::guard('customer')->user();

        if ($type === 'ci') {
            $ci = \App\Models\CommercialInvoice::with(['proformaInvoice.salesOrder.customer.country', 'lines.product', 'lines.batch', 'creator'])->findOrFail($id);
            abort_unless($ci->proformaInvoice?->salesOrder?->customer_id === $customer->id, 404);

            return \Barryvdh\DomPDF\Facade\Pdf::loadView('orders.ci-pdf', ['ci' => $ci])->setPaper('a4')
                ->download($ci->ci_number . '.pdf');
        }

        $pi = \App\Models\ProformaInvoice::with(['salesOrder.customer.country', 'lines.product', 'lines.batch', 'creator'])->findOrFail($id);
        abort_unless($pi->salesOrder?->customer_id === $customer->id, 404);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('orders.pi-pdf', ['pi' => $pi])->setPaper('a4')
            ->download($pi->pi_number . '.pdf');
    }

    // ── Place an order (creates a Purchase Order) ─────────────────────────
    public function createOrder(): View
    {
        abort_unless(Auth::guard('customer')->user()->canPlaceOrders(), 403, 'Ordering is currently disabled.');

        return view('portal.order', [
            'customer' => Auth::guard('customer')->user(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'prn']),
            'order'    => null,
        ]);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer->canPlaceOrders(), 403, 'Ordering is currently disabled.');

        $data = $request->validate([
            'required_by_date'   => 'nullable|date|after_or_equal:today',
            'remarks'            => 'nullable|string|max:2000',
            'promo_code'         => 'nullable|string|max:40',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Validate an optional promo code up-front (order value unknown until quoted).
        $promo = null;
        if (! empty($data['promo_code'])) {
            $promo = \App\Models\PromoCode::findUsable($data['promo_code']);
            if (! $promo || ! $promo->isUsable()) {
                return back()->withInput()->withErrors([
                    'promo_code' => $promo?->invalidReason() ?? 'That promo code is not valid.',
                ]);
            }
        }

        // Attribute to the customer's account manager, else any super admin.
        $createdBy = $customer->manager_id ?: User::where('role', 'super_admin')->value('id');

        $po = DB::transaction(function () use ($data, $customer, $createdBy, $promo) {
            $po = PurchaseOrder::create([
                'po_number'        => PurchaseOrder::nextNumber(),
                'buyer_id'         => $customer->id,
                'created_by'       => $createdBy,
                'po_date'          => now()->toDateString(),
                'required_by_date' => $data['required_by_date'] ?? now()->addDays(30)->toDateString(),
                'currency'         => 'USD',
                'payment_terms'    => '30 days net',
                'status'           => 'sent',   // submitted to us by the customer
                'freight'          => 0,
                'remarks'          => $data['remarks'] ?? null,
                'subtotal'         => 0,
                'total_value'      => 0,
                // Promo snapshot — realised as a discount when the order is invoiced.
                'promo_code_id'       => $promo?->id,
                'promo_code'          => $promo?->code,
                'discount_scope'      => $promo?->scope,
                'discount_type'       => $promo?->discount_type,
                'discount_value'      => $promo?->discount_value,
                'discount_product_id' => $promo?->product_id,
            ]);

            if ($promo) {
                $promo->increment('used_count');
            }

            foreach (array_values($data['items']) as $n => $item) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $item['product_id'],
                    'line_number'       => $n + 1,
                    'quantity'          => (int) $item['quantity'],
                    'unit_price'        => 0,   // to be quoted by staff
                    'line_total'        => 0,
                ]);
            }

            return $po;
        });

        // Notify staff (customer's manager + super admins) and the customer.
        \App\Models\Notification::pushToAdmins([
            'type'         => 'customer_order',
            'severity'     => 'info',
            'title'        => 'New order from customer',
            'message'      => "{$customer->name} placed order {$po->po_number} (" . count($data['items']) . ' line item(s)).',
            'action_url'   => route('orders.po'),
            'action_label' => 'View',
        ], array_filter([$customer->manager_id]));

        $customer->notifyPortal('order', 'Order submitted', "Your order {$po->po_number} was received. We'll confirm pricing shortly.", 'bi-cart-check');

        $promoNote = $promo ? " Promo {$promo->code} applied — {$promo->label}." : '';

        return redirect()->route('portal.dashboard')->with('status', "Order {$po->po_number} placed — we'll be in touch with a quote.{$promoNote}");
    }

    // ── View a single order ───────────────────────────────────────────────
    public function showOrder(PurchaseOrder $purchaseOrder): View
    {
        $order = $this->ownedOrder($purchaseOrder);
        $order->load('lines.product', 'documents', 'creator', 'salesOrder.proformaInvoice.commercialInvoices:id,proforma_invoice_id');

        return view('portal.order-show', [
            'customer' => Auth::guard('customer')->user(),
            'order'    => $order,
            'chain'    => $order->chainStages(),
            'editable' => $order->isEditableByCustomer(),
            'portal'   => PortalSettings::all(),
        ]);
    }

    // ── Edit an order (only while still editable) ─────────────────────────
    public function editOrder(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        $order = $this->ownedOrder($purchaseOrder);

        if (! $order->isEditableByCustomer()) {
            return redirect()->route('portal.order.show', $order)
                ->with('status', "Order {$order->po_number} can no longer be edited — it's already being processed.");
        }

        $order->load('lines');

        return view('portal.order', [
            'customer' => Auth::guard('customer')->user(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'prn']),
            'order'    => $order,
        ]);
    }

    public function updateOrder(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $order = $this->ownedOrder($purchaseOrder);
        abort_unless($order->isEditableByCustomer(), 403, 'This order can no longer be edited.');

        $data = $request->validate([
            'required_by_date'   => 'nullable|date|after_or_equal:today',
            'remarks'            => 'nullable|string|max:2000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($data, $order) {
            $order->update([
                'required_by_date' => $data['required_by_date'] ?? $order->required_by_date,
                'remarks'          => $data['remarks'] ?? null,
            ]);

            // Replace the lines — pricing is (re)quoted by staff, so reset to 0.
            $order->lines()->delete();
            foreach (array_values($data['items']) as $n => $item) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $order->id,
                    'product_id'        => $item['product_id'],
                    'line_number'       => $n + 1,
                    'quantity'          => (int) $item['quantity'],
                    'unit_price'        => 0,
                    'line_total'        => 0,
                ]);
            }
        });

        $customer = Auth::guard('customer')->user();

        \App\Models\Notification::pushToAdmins([
            'type'         => 'customer_order',
            'severity'     => 'info',
            'title'        => 'Order updated by customer',
            'message'      => "{$customer->name} updated order {$order->po_number} (" . count($data['items']) . ' line item(s)).',
            'action_url'   => route('orders.po'),
            'action_label' => 'View',
        ], array_filter([$customer->manager_id]));

        return redirect()->route('portal.order.show', $order)
            ->with('status', "Order {$order->po_number} updated.");
    }

    public function cancelOrder(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $order = $this->ownedOrder($purchaseOrder);
        abort_unless($order->isEditableByCustomer(), 403, 'This order can no longer be cancelled.');

        $order->update(['status' => 'cancelled']);

        $customer = Auth::guard('customer')->user();

        \App\Models\Notification::pushToAdmins([
            'type'         => 'customer_order',
            'severity'     => 'warning',
            'title'        => 'Order cancelled by customer',
            'message'      => "{$customer->name} cancelled order {$order->po_number}.",
            'action_url'   => route('orders.po'),
            'action_label' => 'View',
        ], array_filter([$customer->manager_id]));

        return redirect()->route('portal.dashboard')
            ->with('status', "Order {$order->po_number} has been cancelled.");
    }

    /** Ensure the order belongs to the signed-in customer (404 otherwise). */
    private function ownedOrder(PurchaseOrder $po): PurchaseOrder
    {
        abort_unless($po->buyer_id === Auth::guard('customer')->id(), 404);

        return $po;
    }

    // ── In-app notifications ──────────────────────────────────────────────
    public function markNotificationsRead(): RedirectResponse
    {
        Auth::guard('customer')->user()->portalNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    // ── Profile self-edit ─────────────────────────────────────────────────
    public function editProfile(): View
    {
        abort_unless(PortalSettings::get('portal_allow_profile_edit'), 403, 'Profile editing is disabled.');

        return view('portal.profile', [
            'customer'  => Auth::guard('customer')->user()->load('country', 'manager'),
            'countries' => Country::orderBy('name')->get(['id', 'name', 'flag']),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        abort_unless(PortalSettings::get('portal_allow_profile_edit'), 403, 'Profile editing is disabled.');

        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'name'         => 'required|string|max:160',
            'email'        => 'required|email|max:160|unique:customers,email,' . $customer->id,
            'phone'        => 'nullable|string|max:40',
            'country_id'   => 'required|exists:countries,id',
            'city'         => 'nullable|string|max:120',
            'address'      => 'nullable|string|max:500',
            'company_name' => 'nullable|string|max:160',
            'company_id'   => 'nullable|string|max:100',
            'company_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'password'     => ['nullable', 'confirmed', PasswordRule::min(8)],
        ]);

        if ($request->hasFile('company_logo')) {
            if ($customer->company_logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($customer->company_logo);
            }
            $data['company_logo'] = $request->file('company_logo')->store('customers/logos', 'public');
        } else {
            unset($data['company_logo']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $customer->update($data);

        return redirect()->route('portal.profile')->with('status', 'Your profile has been updated.');
    }

    // ── Self-registration ─────────────────────────────────────────────────
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('portal.dashboard');
        }

        if (! PortalSettings::get('portal_allow_registration')) {
            return redirect()->route('portal.login')->with('status', 'Self-registration is currently closed. Please contact us to open an account.');
        }

        return view('portal.register', [
            'countries' => Country::orderBy('name')->get(['id', 'name', 'flag']),
            'types'     => Customer::TYPES,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        abort_unless(PortalSettings::get('portal_allow_registration'), 403, 'Self-registration is disabled.');

        $data = $request->validate([
            'name'         => 'required|string|max:160',
            'type'         => 'required|in:' . implode(',', array_keys(Customer::TYPES)),
            'company_name' => 'nullable|string|max:160',
            'email'        => 'required|email|max:160|unique:customers,email',
            'phone'        => 'nullable|string|max:40',
            'country_id'   => 'required|exists:countries,id',
            'city'         => 'nullable|string|max:120',
            'password'     => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $customer = Customer::create([
            ...$data,
            'customer_code' => Customer::nextCode(),
            'status'        => 'pending',   // awaits staff approval before login
        ]);

        // Alert staff (super admins) that a new customer needs approval.
        \App\Models\Notification::pushToAdmins([
            'type'         => 'customer_registration',
            'severity'     => 'info',
            'title'        => 'New customer registration',
            'message'      => "{$customer->name} ({$customer->type_label}) registered and awaits approval.",
            'action_url'   => route('customers.index', ['status' => 'pending']),
            'action_label' => 'Review',
        ]);

        return redirect()->route('portal.login')
            ->with('status', 'Thanks for registering! Your account is pending review — we\'ll email you once it\'s approved.');
    }

    // ── Password reset ────────────────────────────────────────────────────
    public function showForgot(): View
    {
        return view('portal.forgot');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('customers')->sendResetLink($request->only('email'));

        return back()->with('status', __($status));
    }

    public function showReset(Request $request, string $token): View
    {
        return view('portal.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill([
                    'password'       => $password,   // 'hashed' cast hashes it
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('portal.login')->with('status', 'Your password has been reset — you can now sign in.')
            : back()->withErrors(['email' => __($status)]);
    }
}

