<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales reporting & analytics. "Sales" = Sales Orders in a confirmed-or-later
 * state. Super admins see all; managers see only their own (created_by).
 */
class ReportController extends Controller
{
    private const SALES_STATUSES = ['confirmed', 'pi_issued', 'completed'];

    public function index(Request $request): View
    {
        return view('reports', $this->gather($request));
    }

    /** CSV export of the current (filtered, scoped) report. */
    public function csv(Request $request): StreamedResponse
    {
        $d = $this->gather($request);
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="sales-report-' . now()->format('Ymd_His') . '.csv"'];

        return response()->streamDownload(function () use ($d) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Sales Report', ($d['mine'] ? 'My data' : 'All data'), 'Generated', now()->format('Y-m-d H:i')]);
            fputcsv($out, []);
            fputcsv($out, ['Summary']);
            fputcsv($out, ['Total Sales', $d['summary']['total_sales']]);
            fputcsv($out, ['Sales Orders', $d['summary']['orders']]);
            fputcsv($out, ['Units Sold', $d['summary']['units']]);
            fputcsv($out, ['Customers', $d['summary']['customers']]);
            fputcsv($out, []);
            fputcsv($out, ['Customer-wise Sales']);
            fputcsv($out, ['Customer', 'Orders', 'Sales Value']);
            foreach ($d['customerWise'] as $c) fputcsv($out, [$c['name'], $c['orders'], $c['value']]);
            fputcsv($out, []);
            fputcsv($out, ['Product-wise Sales']);
            fputcsv($out, ['Product', 'Quantity', 'Sales Value']);
            foreach ($d['productWise'] as $p) fputcsv($out, [$p['name'], $p['qty'], $p['value']]);
            fputcsv($out, []);
            fputcsv($out, ['Monthly Sales (' . $d['year'] . ')']);
            foreach ($d['monthly'] as $m) fputcsv($out, [$m['label'], $m['value']]);
            fclose($out);
        }, 'sales-report.csv', $headers);
    }

    /** PDF export of the current (filtered, scoped) report. */
    public function pdf(Request $request)
    {
        $d = $this->gather($request);
        $d['issued'] = now();
        return Pdf::loadView('reports-pdf', $d)->setPaper('a4')->download('sales-report-' . now()->format('Ymd') . '.pdf');
    }

    /** Build the scoped, filtered report dataset shared by index/csv/pdf. */
    private function gather(Request $request): array
    {
        $user = $request->user();
        $mine = !$user->seesAllData();
        $uid  = $user->id;

        $year = (int) ($request->query('year') ?: now()->year);
        $from = $request->query('from');
        $to   = $request->query('to');

        // Base sales-order query with scope + date filter, reusable via clone.
        $base = SalesOrder::whereIn('status', self::SALES_STATUSES)
            ->when($mine, fn ($q) => $q->where('created_by', $uid))
            ->when($from, fn ($q) => $q->whereDate('so_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('so_date', '<=', $to));

        // ── Summary ──
        $summary = [
            'total_sales' => (float) (clone $base)->sum('total_value'),
            'orders'      => (clone $base)->count(),
            'customers'   => (clone $base)->distinct('customer_id')->count('customer_id'),
            'units'       => (int) DB::table('sales_order_lines as l')->join('sales_orders as s', 's.id', '=', 'l.sales_order_id')
                ->whereIn('s.status', self::SALES_STATUSES)
                ->when($mine, fn ($q) => $q->where('s.created_by', $uid))
                ->when($from, fn ($q) => $q->whereDate('s.so_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('s.so_date', '<=', $to))
                ->sum('l.quantity'),
            'pos'         => PurchaseOrder::when($mine, fn ($q) => $q->where('created_by', $uid))->count(),
        ];

        // ── Monthly trend (selected year, ignores from/to so the year view is whole) ──
        $monthRows = SalesOrder::whereIn('status', self::SALES_STATUSES)
            ->when($mine, fn ($q) => $q->where('created_by', $uid))
            ->whereYear('so_date', $year)
            ->selectRaw('MONTH(so_date) as m, SUM(total_value) as v')->groupBy('m')->pluck('v', 'm');
        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthly[] = ['label' => date('M', mktime(0, 0, 0, $m, 1)), 'value' => round((float) ($monthRows[$m] ?? 0), 2)];
        }

        // ── Yearly (last 5 years) ──
        $yearRows = SalesOrder::whereIn('status', self::SALES_STATUSES)
            ->when($mine, fn ($q) => $q->where('created_by', $uid))
            ->selectRaw('YEAR(so_date) as y, SUM(total_value) as v')->groupBy('y')->pluck('v', 'y');
        $yearly = [];
        for ($y = now()->year - 4; $y <= now()->year; $y++) {
            $yearly[] = ['label' => (string) $y, 'value' => round((float) ($yearRows[$y] ?? 0), 2)];
        }

        // ── Customer-wise (top 8 by value) ──
        $custRows = (clone $base)->selectRaw('customer_id, SUM(total_value) as v, COUNT(*) as c')
            ->groupBy('customer_id')->orderByDesc('v')->limit(8)->get();
        $custNames = Customer::whereIn('id', $custRows->pluck('customer_id'))->pluck('name', 'id');
        $customerWise = $custRows->map(fn ($r) => [
            'name'   => $custNames[$r->customer_id] ?? ('#' . $r->customer_id),
            'value'  => round((float) $r->v, 2),
            'orders' => (int) $r->c,
        ])->all();

        // ── Product-wise (value + qty, top 10) ──
        $prodRows = DB::table('sales_order_lines as l')->join('sales_orders as s', 's.id', '=', 'l.sales_order_id')
            ->whereIn('s.status', self::SALES_STATUSES)
            ->when($mine, fn ($q) => $q->where('s.created_by', $uid))
            ->when($from, fn ($q) => $q->whereDate('s.so_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('s.so_date', '<=', $to))
            ->groupBy('l.product_id')
            ->selectRaw('l.product_id, SUM(l.line_total) as v, SUM(l.quantity) as q')
            ->orderByDesc('v')->limit(10)->get();
        $prodNames = Product::whereIn('id', $prodRows->pluck('product_id'))->pluck('name', 'id');
        $productWise = $prodRows->map(fn ($r) => [
            'name'  => $prodNames[$r->product_id] ?? ('#' . $r->product_id),
            'value' => round((float) $r->v, 2),
            'qty'   => (int) $r->q,
        ])->all();

        // ── PO / SO status counts (donut) ──
        $soStatus = SalesOrder::when($mine, fn ($q) => $q->where('created_by', $uid))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();
        $poStatus = PurchaseOrder::when($mine, fn ($q) => $q->where('created_by', $uid))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->all();

        $years = range(now()->year, now()->year - 4);

        return compact(
            'mine', 'year', 'from', 'to', 'years', 'summary',
            'monthly', 'yearly', 'customerWise', 'productWise', 'soStatus', 'poStatus'
        );
    }
}
