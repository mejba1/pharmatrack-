<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $orders = $customer->purchaseOrders()->with('lines.product')->latest()->limit(20)->get();
        $units  = $customer->soldUnits()->with('batch.product')->latest('sold_at')->limit(50)->get();

        $stats = [
            'orders' => $customer->purchaseOrders()->count(),
            'units'  => $customer->soldUnits()->count(),
            'sales'  => $customer->sales()->count(),
        ];

        return view('portal.dashboard', compact('customer', 'orders', 'units', 'stats'));
    }
}
