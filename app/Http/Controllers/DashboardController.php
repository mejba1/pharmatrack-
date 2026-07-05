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
        $uid  = $user->id;

        // Per-area visibility: a user sees only their own records unless a
        // super admin granted the "{module}.view_all" scope for that area.
        $ordersMine = !$user->canViewAll('orders');    // PO / SO
        $invMine    = !$user->canViewAll('invoices');  // PI / CI
        $custMine   = !$user->canViewAll('customers');
        $mine       = !$user->seesAllData();           // generic flag for the view label

        // A scoped user sees records they created OR that belong to their
        // customers (assigned account manager + managed countries).
        $ownIds = $user->ownedCustomerIds();
        $ownPo = fn ($q) => $ordersMine ? $q->where(fn ($w) => $w->where('created_by', $uid)->orWhereIn('buyer_id', $ownIds ?: [0])) : $q;
        $ownSo = fn ($q) => $ordersMine ? $q->where(fn ($w) => $w->where('created_by', $uid)->orWhereIn('customer_id', $ownIds ?: [0])) : $q;
        $ownPi = fn ($q) => $invMine ? $q->where(fn ($w) => $w->where('created_by', $uid)->orWhereHas('salesOrder', fn ($s) => $s->whereIn('customer_id', $ownIds ?: [0]))) : $q;
        $ownCi = fn ($q) => $invMine ? $q->where(fn ($w) => $w->where('created_by', $uid)->orWhereHas('proformaInvoice.salesOrder', fn ($s) => $s->whereIn('customer_id', $ownIds ?: [0]))) : $q;
        $cust  = fn ($q) => $custMine ? $q->whereIn('id', $ownIds ?: [0]) : $q;

        $stats = [
            'po_total'        => $ownPo(PurchaseOrder::query())->count(),
            'po_pending'      => $ownPo(PurchaseOrder::where('status', 'sent'))->count(),
            'po_acknowledged' => $ownPo(PurchaseOrder::where('status', 'acknowledged'))->count(),
            'so_total'        => $ownSo(SalesOrder::query())->count(),
            'so_confirmed'    => $ownSo(SalesOrder::where('status', 'confirmed'))->count(),
            'pi_total'        => $ownPi(ProformaInvoice::query())->count(),
            'pi_pending'      => $ownPi(ProformaInvoice::whereIn('status', ['sent', 'pending_approval']))->count(),
            'pi_approved'     => $ownPi(ProformaInvoice::where('status', 'approved'))->count(),
            'ci_total'        => $ownCi(CommercialInvoice::query())->count(),
            'customers'       => $cust(Customer::query())->count(),
        ];

        // Total order value the user is responsible for (their POs).
        $stats['order_value'] = (float) $ownPo(PurchaseOrder::query())->sum('total_value');

        // Pipeline funnel (their / global counts).
        $funnel = [
            ['label' => 'Purchase Orders',   'count' => $stats['po_total'], 'icon' => 'bi-cart3',             'tone' => 'primary', 'route' => route('orders.po')],
            ['label' => 'Sales Orders',      'count' => $stats['so_total'], 'icon' => 'bi-bag-check',         'tone' => 'info',    'route' => route('orders.so')],
            ['label' => 'Proforma Invoices', 'count' => $stats['pi_total'], 'icon' => 'bi-receipt',           'tone' => 'warning', 'route' => route('orders.pi')],
            ['label' => 'Commercial Invoices','count' => $stats['ci_total'],'icon' => 'bi-file-earmark-check','tone' => 'success', 'route' => route('orders.ci')],
        ];

        $recentPos = $ownPo(PurchaseOrder::with('buyer'))->latest()->limit(6)->get();
        $recentSos = $ownSo(SalesOrder::with('customer'))->latest()->limit(6)->get();
        $recentCustomers = $cust(Customer::with('country'))->latest()->limit(6)->get();

        return view('dashboard', compact('user', 'mine', 'stats', 'funnel', 'recentPos', 'recentSos', 'recentCustomers'));
    }
}
