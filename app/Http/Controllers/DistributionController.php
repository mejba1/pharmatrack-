<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchUnit;
use App\Models\Consignment;
use App\Models\MasterCarton;
use App\Models\MasterCartonContent;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Distribution dashboard, hierarchy (Product → Batch → Master Carton →
 * Shipment) and universal traceability search.
 */
class DistributionController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────

    public function index(): View
    {
        $stats = [
            'batches'    => Batch::count(),
            'cartons'    => MasterCarton::count(),
            'shipments'  => Consignment::count(),
            'in_transit' => Consignment::whereIn('status', ['dispatched', 'in_transit'])->count(),
            'received'   => MasterCarton::whereNotNull('received_at')->count(),
            // In transit: dispatched, not yet received, not flagged missing.
            'pending'    => MasterCarton::whereNull('received_at')
                                ->where('carton_condition', '!=', 'missing')
                                ->whereHas('consignment', fn ($c) => $c->whereNotNull('dispatched_at'))
                                ->count(),
            // Genuinely missing/short: a receiver marked it as never arrived.
            'missing'    => MasterCarton::where('carton_condition', 'missing')->count(),
            'damaged'    => MasterCarton::where('carton_condition', 'damaged')->count(),
        ];

        $recentShipments = Consignment::with('cartons')->orderByDesc('id')->limit(8)->get();

        $missingCartons = MasterCarton::with(['consignment', 'product', 'batch'])
            ->where('carton_condition', 'missing')
            ->orderByDesc('updated_at')->limit(15)->get();

        $damagedCartons = MasterCarton::with(['consignment', 'product', 'batch'])
            ->where('carton_condition', 'damaged')
            ->orderByDesc('updated_at')->limit(15)->get();

        $hierarchy = $this->hierarchy();

        return view('distribution', compact('stats', 'recentShipments', 'missingCartons', 'damagedCartons', 'hierarchy'));
    }

    /** Product → Batch → carton/shipment rollup for the hierarchy tree. */
    private function hierarchy()
    {
        $batchIds = MasterCarton::whereNotNull('batch_id')->distinct()->pluck('batch_id')
            ->merge(MasterCartonContent::distinct()->pluck('batch_id'))
            ->unique()->filter()->values();

        if ($batchIds->isEmpty()) {
            return collect();
        }

        $batches = Batch::with('product')->whereIn('id', $batchIds)->get();

        return $batches->groupBy(fn ($b) => $b->product?->name ?? '—')->map(function ($group, $productName) {
            $rows = $group->map(function ($b) {
                $cartonIds = MasterCartonContent::where('batch_id', $b->id)->distinct()->pluck('master_carton_id')
                    ->merge(MasterCarton::where('batch_id', $b->id)->pluck('id'))->unique();

                return [
                    'brn'        => $b->brn,
                    'batch_no'   => $b->batch_number,
                    'cartons'    => $cartonIds->count(),
                    'units'      => (int) MasterCartonContent::where('batch_id', $b->id)->sum('quantity'),
                    'shipments'  => MasterCarton::whereIn('id', $cartonIds)->whereNotNull('consignment_id')
                                        ->distinct()->count('consignment_id'),
                ];
            })->sortBy('brn')->values();

            return [
                'product'   => $productName,
                'batches'   => $rows,
                'cartons'   => $rows->sum('cartons'),
                'shipments' => $rows->sum('shipments'),
            ];
        })->sortKeys()->values();
    }

    // ── Universal traceability search ─────────────────────────────────────

    public function lookup(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json(['results' => []]);
        }

        $results = collect();

        // Shipments (number / QR).
        Consignment::with('cartons')
            ->where('consignment_number', 'like', "%{$q}%")
            ->orWhere('qr_code', $q)
            ->limit(5)->get()
            ->each(fn ($c) => $results->push([
                'kind'        => 'Shipment',
                'headline'    => $c->consignment_number,
                'product'     => $c->product_list->join(', ') ?: '—',
                'batch'       => $c->batch_list->join(', ') ?: '—',
                'carton'      => $c->carton_count . ' cartons',
                'shipment'    => $c->consignment_number,
                'status'      => $c->status_label,
                'status_badge'=> $c->status_badge_class,
                'serial'      => '—',
                'link'        => route('shipment.scan', $c->qr_code),
            ]));

        // Cartons (number / QR).
        MasterCarton::with(['consignment', 'product', 'batch'])
            ->where('carton_number', 'like', "%{$q}%")
            ->orWhere('qr_code', $q)
            ->limit(10)->get()
            ->each(fn ($c) => $results->push($this->cartonRow($c)));

        // Serial number → which carton holds it.
        if (ctype_digit($q)) {
            $serial = (int) $q;
            MasterCartonContent::with(['carton.consignment', 'product', 'batch'])
                ->where('serial_start', '<=', $serial)
                ->where('serial_end', '>=', $serial)
                ->limit(20)->get()
                ->each(function ($ct) use ($results, $serial) {
                    if ($ct->carton) {
                        $results->push($this->cartonRow($ct->carton, $serial, $ct));
                    }
                });
        }

        // Batch reference / number.
        $batchIds = Batch::where('brn', 'like', "%{$q}%")->orWhere('batch_number', 'like', "%{$q}%")->limit(5)->pluck('id');
        if ($batchIds->isNotEmpty()) {
            MasterCarton::with(['consignment', 'product', 'batch'])
                ->whereIn('batch_id', $batchIds)
                ->orWhereHas('contents', fn ($c) => $c->whereIn('batch_id', $batchIds))
                ->limit(15)->get()
                ->each(fn ($c) => $results->push($this->cartonRow($c)));
        }

        // De-duplicate (a carton can match several ways).
        $unique = $results->unique(fn ($r) => $r['kind'] . '|' . $r['headline'] . '|' . $r['serial'])->values();

        return response()->json(['results' => $unique->take(40)->all()]);
    }

    private function cartonRow(MasterCarton $c, ?int $serial = null, ?MasterCartonContent $content = null): array
    {
        $product = $content?->product?->name ?? $c->products_summary;
        $batch   = $content?->batch?->brn ?? $c->batches_summary;

        return [
            'kind'         => $serial ? 'Serial ' . $serial : 'Carton',
            'headline'     => $c->carton_number,
            'product'      => $product,
            'batch'        => $batch,
            'carton'       => $c->carton_number,
            'shipment'     => $c->consignment?->consignment_number ?? '—',
            'status'       => $c->status_label,
            'status_badge' => $c->status_badge_class,
            'serial'       => $serial ? (string) $serial : ($content ? "{$content->serial_start}–{$content->serial_end}" : $c->serial_range),
            'link'         => route('carton.scan', $c->qr_code),
        ];
    }
}
