<?php

namespace App\Http\Controllers;

use App\Models\CommercialInvoice;
use App\Models\Customer;
use App\Models\ProformaInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Role-aware dashboard. Super admins see global figures; every other user
 * (country manager) sees only their own activity — the orders they created
 * (PO/SO/PI/CI all carry created_by, copied down the chain) and the customers
 * in their managed countries.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $mine = !$user->seesAllData();
        $uid  = $user->id;

        // created_by scope for the order entities.
        $own = fn ($q) => $mine ? $q->where('created_by', $uid) : $q;
        // assignment scope for customers (assigned account manager).
        $cust = fn ($q) => $mine ? $q->where('manager_id', $uid) : $q;

        $stats = [
            'po_total'        => $own(PurchaseOrder::query())->count(),
            'po_pending'      => $own(PurchaseOrder::where('status', 'sent'))->count(),
            'po_acknowledged' => $own(PurchaseOrder::where('status', 'acknowledged'))->count(),
            'so_total'        => $own(SalesOrder::query())->count(),
            'so_confirmed'    => $own(SalesOrder::where('status', 'confirmed'))->count(),
            'pi_total'        => $own(ProformaInvoice::query())->count(),
            'pi_pending'      => $own(ProformaInvoice::whereIn('status', ['sent', 'pending_approval']))->count(),
            'pi_approved'     => $own(ProformaInvoice::where('status', 'approved'))->count(),
            'ci_total'        => $own(CommercialInvoice::query())->count(),
            'customers'       => $cust(Customer::query())->count(),
        ];

        // Total order value the user is responsible for (their POs).
        $stats['order_value'] = (float) $own(PurchaseOrder::query())->sum('total_value');

        // Pipeline funnel (their / global counts).
        $funnel = [
            ['label' => 'Purchase Orders',   'count' => $stats['po_total'], 'icon' => 'bi-cart3',             'tone' => 'primary', 'route' => route('orders.po')],
            ['label' => 'Sales Orders',      'count' => $stats['so_total'], 'icon' => 'bi-bag-check',         'tone' => 'info',    'route' => route('orders.so')],
            ['label' => 'Proforma Invoices', 'count' => $stats['pi_total'], 'icon' => 'bi-receipt',           'tone' => 'warning', 'route' => route('orders.pi')],
            ['label' => 'Commercial Invoices','count' => $stats['ci_total'],'icon' => 'bi-file-earmark-check','tone' => 'success', 'route' => route('orders.ci')],
        ];

        $recentPos = $own(PurchaseOrder::with('buyer'))->latest()->limit(6)->get();
        $recentSos = $own(SalesOrder::with('customer'))->latest()->limit(6)->get();
        $recentCustomers = $cust(Customer::with('country'))->latest()->limit(6)->get();

        return view('dashboard', compact('user', 'mine', 'stats', 'funnel', 'recentPos', 'recentSos', 'recentCustomers'));
    }
}
