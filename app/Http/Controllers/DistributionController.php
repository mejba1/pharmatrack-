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
        $stats = \Illuminate\Support\Facades\Cache::remember('dist_stats', 60, fn () => [
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
        ]);

        $recentShipments = Consignment::orderByDesc('id')->limit(8)->get();

        $missingCartons = MasterCarton::with(['consignment', 'product', 'batch'])
            ->where('carton_condition', 'missing')
            ->orderByDesc('updated_at')->limit(15)->get();

        $damagedCartons = MasterCarton::with(['consignment', 'product', 'batch'])
            ->where('carton_condition', 'damaged')
            ->orderByDesc('updated_at')->limit(15)->get();

        $hierarchy = $this->hierarchy();
        $products  = Product::orderBy('name')->get(['id', 'name', 'prn']);

        return view('distribution', compact('stats', 'recentShipments', 'missingCartons', 'damagedCartons', 'hierarchy', 'products'));
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
        $q         = trim((string) $request->query('q', ''));
        $productId = $request->query('product_id') ?: null;
        $batchId   = $request->query('batch_id') ?: null;

        if ($q === '' && !$productId && !$batchId) {
            return response()->json(['results' => []]);
        }

        $results = collect();
        $serials = $this->parseSerials($q);   // single, list (1,2,3) or range (1-50)

        // ── Serial trace (optionally scoped to a product and/or batch) ──
        if (!empty($serials)) {
            $query = MasterCartonContent::with(['carton.consignment', 'product', 'batch']);
            if ($batchId)        $query->where('batch_id', $batchId);
            elseif ($productId)  $query->where('product_id', $productId);

            // Any content whose range covers one of the requested serials.
            $query->where(function ($w) use ($serials) {
                foreach ($serials as $s) {
                    $w->orWhere(fn ($x) => $x->where('serial_start', '<=', $s)->where('serial_end', '>=', $s));
                }
            });

            foreach ($query->limit(200)->get() as $ct) {
                if (!$ct->carton) continue;
                foreach ($serials as $s) {
                    if ($ct->serial_start <= $s && $ct->serial_end >= $s) {
                        $results->push($this->cartonRow($ct->carton, $s, $ct));
                    }
                }
            }
        }

        // ── Free-text entity search (skip if the query was purely serials) ──
        if ($q !== '' && empty($serials)) {
            Consignment::where('consignment_number', 'like', "%{$q}%")
                ->orWhere('qr_code', $q)
                ->limit(5)->get()
                ->each(fn ($c) => $results->push([
                    'kind' => 'Shipment', 'headline' => $c->consignment_number,
                    'product' => '—', 'batch' => '—', 'carton' => $c->carton_count . ' cartons',
                    'shipment' => $c->consignment_number, 'status' => $c->status_label,
                    'status_badge' => $c->status_badge_class, 'serial' => '—',
                    'link' => route('shipment.scan', $c->qr_code),
                ]));

            MasterCarton::with(['consignment', 'product', 'batch'])
                ->where('carton_number', 'like', "%{$q}%")->orWhere('qr_code', $q)
                ->when($batchId, fn ($w) => $w->where('batch_id', $batchId))
                ->limit(10)->get()
                ->each(fn ($c) => $results->push($this->cartonRow($c)));

            $batchIds = Batch::where('brn', 'like', "%{$q}%")->orWhere('batch_number', 'like', "%{$q}%")->limit(5)->pluck('id');
            if ($batchIds->isNotEmpty()) {
                MasterCarton::with(['consignment', 'product', 'batch'])
                    ->whereIn('batch_id', $batchIds)
                    ->orWhereHas('contents', fn ($c) => $c->whereIn('batch_id', $batchIds))
                    ->limit(15)->get()
                    ->each(fn ($c) => $results->push($this->cartonRow($c)));
            }
        } elseif (empty($serials) && ($productId || $batchId)) {
            // Browse cartons for a chosen product/batch with no serial given.
            MasterCarton::with(['consignment', 'product', 'batch'])
                ->when($batchId, fn ($w) => $w->where('batch_id', $batchId))
                ->when(!$batchId && $productId, fn ($w) => $w->where('product_id', $productId))
                ->orderByDesc('id')->limit(25)->get()
                ->each(fn ($c) => $results->push($this->cartonRow($c)));
        }

        $unique = $results->unique(fn ($r) => $r['kind'] . '|' . $r['headline'] . '|' . $r['serial'])->values();

        return response()->json(['results' => $unique->take(100)->all()]);
    }

    /**
     * Parse a serial query into a bounded list of integers.
     * Accepts "12345", "1,2,3", "1-50", or a mix — capped to 200 serials.
     */
    private function parseSerials(string $q): array
    {
        if ($q === '' || !preg_match('/^[\d\s,\-]+$/', $q)) {
            return [];
        }
        $serials = [];
        foreach (preg_split('/[\s,]+/', trim($q), -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (preg_match('/^(\d+)-(\d+)$/', $token, $m)) {
                [$a, $b] = [(int) $m[1], (int) $m[2]];
                if ($b < $a) [$a, $b] = [$b, $a];
                for ($i = $a; $i <= $b && count($serials) < 200; $i++) {
                    $serials[] = $i;
                }
            } elseif (ctype_digit($token)) {
                $serials[] = (int) $token;
            }
            if (count($serials) >= 200) break;
        }
        return array_values(array_unique($serials));
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
