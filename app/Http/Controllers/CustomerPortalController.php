<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
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

        return view('portal.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::guard('customer')->attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->onlyInput('email');
        }

        $customer = Auth::guard('customer')->user();
        if ($customer->status !== 'active') {
            Auth::guard('customer')->logout();

            return back()->withErrors(['email' => 'Your account is not active. Please contact us.'])->onlyInput('email');
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
            ->latest()->limit(20)->get();
        $units  = $customer->soldUnits()->with('batch.product')->latest('sold_at')->limit(50)->get();

        // Invoices raised against this customer's sales orders.
        $pis = \App\Models\ProformaInvoice::whereHas('salesOrder', fn ($q) => $q->where('customer_id', $cid))
            ->latest('pi_date')->get();
        $cis = \App\Models\CommercialInvoice::whereHas('proformaInvoice.salesOrder', fn ($q) => $q->where('customer_id', $cid))
            ->latest('ci_date')->get();

        $invoices = $pis->map(fn ($p) => [
            'type' => 'Proforma', 'number' => $p->pi_number, 'date' => $p->pi_date?->format('d M Y'),
            'currency' => $p->currency, 'total' => (float) $p->total_value, 'status' => $p->status_label ?? ucfirst((string) $p->status),
        ])->concat($cis->map(fn ($c) => [
            'type' => 'Commercial', 'number' => $c->ci_number, 'date' => $c->ci_date?->format('d M Y'),
            'currency' => $c->currency, 'total' => (float) ($c->total_value ?? 0), 'status' => ucfirst((string) $c->status),
        ]))->values();

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

        return view('portal.dashboard', compact('customer', 'orders', 'units', 'invoices', 'documents', 'stats'));
    }

    // ── Place an order (creates a Purchase Order) ─────────────────────────
    public function createOrder(): View
    {
        return view('portal.order', [
            'customer' => Auth::guard('customer')->user(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'prn']),
        ]);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'required_by_date'   => 'nullable|date|after_or_equal:today',
            'remarks'            => 'nullable|string|max:2000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Attribute to the customer's account manager, else any super admin.
        $createdBy = $customer->manager_id ?: User::where('role', 'super_admin')->value('id');

        $po = DB::transaction(function () use ($data, $customer, $createdBy) {
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
            ]);

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

        return redirect()->route('portal.dashboard')->with('status', "Order {$po->po_number} placed — we'll be in touch with a quote.");
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
        return view('portal.profile', [
            'customer'  => Auth::guard('customer')->user()->load('country', 'manager'),
            'countries' => Country::orderBy('name')->get(['id', 'name', 'flag']),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
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

        return view('portal.register', [
            'countries' => Country::orderBy('name')->get(['id', 'name', 'flag']),
            'types'     => Customer::TYPES,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
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

