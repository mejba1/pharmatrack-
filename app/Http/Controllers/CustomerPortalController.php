<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $orders = $customer->purchaseOrders()->with('lines.product', 'documents')->latest()->limit(20)->get();
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

        // Documents attached to this customer's purchase orders (public storage URLs).
        $documents = $orders->flatMap(fn ($po) => $po->documents)->map(fn ($d) => [
            'name' => $d->name, 'category' => $d->category, 'size' => $d->size_human,
            'date' => $d->created_at?->format('d M Y'), 'url' => $d->url, 'icon' => $d->icon_class ?? 'text-secondary',
        ])->values();

        $stats = [
            'orders'    => $customer->purchaseOrders()->count(),
            'units'     => $customer->soldUnits()->count(),
            'invoices'  => $pis->count() + $cis->count(),
            'documents' => $documents->count(),
        ];

        return view('portal.dashboard', compact('customer', 'orders', 'units', 'invoices', 'documents', 'stats'));
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

        Customer::create([
            ...$data,
            'customer_code' => Customer::nextCode(),
            'status'        => 'pending',   // awaits staff approval before login
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

