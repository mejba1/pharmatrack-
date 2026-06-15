<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchUnit;
use App\Models\MasterCarton;
use App\Models\MasterCartonContent;
use App\Models\MasterCartonScan;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Master Carton Management — internal Factory→Depot distribution.
 *
 * Uses a GS1-style aggregation model: a carton (logistic unit / SSCC-equivalent)
 * may be generic (no product/batch until packed) and may hold multiple
 * product/batch/serial segments (mixed). Packed segments live in
 * master_carton_contents; the carton keeps a homogeneous summary for the
 * common single-product case.
 */
class MasterCartonController extends Controller
{
    private const MAX_CARTONS = 100000;

    // ── Index (list + stats + batch-wise summary) ─────────────────────────

    public function index(Request $request): View
    {
        $filters = $request->only(['product_id', 'batch_id', 'status', 'search', 'fill', 'date_from', 'date_to']);

        // Cursor (keyset) pagination — no COUNT(*), no deep OFFSET on millions.
        $cartons = MasterCarton::with(['product', 'batch', 'contents.product', 'contents.batch'])
                    ->filter($filters)
                    ->orderByDesc('id')
                    ->cursorPaginate(20)
                    ->withQueryString();

        // Cached so the 5 COUNT(*)s aren't recomputed on every request.
        $stats = Cache::remember('mc_stats', 60, fn () => [
            'total'      => MasterCarton::count(),
            'packed'     => MasterCarton::where('packed_quantity', '>', 0)->count(),
            'empty'      => MasterCarton::where('packed_quantity', 0)->count(),
            'dispatched' => MasterCarton::where('status', 'dispatched')->count(),
            'received'   => MasterCarton::where('status', 'received')->count(),
        ]);

        // Batch-wise summary is heavy; it's loaded lazily via batchSummaryView().
        $products = Product::orderBy('name')->get(['id', 'name', 'prn']);
        $batches  = Batch::orderByDesc('id')->limit(500)->get(['id', 'brn', 'batch_number', 'product_id']);

        return view('master-cartons', compact('cartons', 'stats', 'filters', 'products', 'batches'));
    }

    /** Lazy-loaded, bounded batch-wise summary (AJAX partial). */
    public function batchSummaryView(): View
    {
        $summary = Cache::remember('mc_batch_summary', 60, fn () => $this->batchSummary());
        return view('partials.carton-batch-summary', compact('summary'));
    }

    /** Invalidate cached stat counters + batch summary after a mutation. */
    private function forgetCaches(): void
    {
        Cache::forget('mc_stats');
        Cache::forget('mc_batch_summary');
        Cache::forget('dist_stats');
    }

    /**
     * Batch-wise carton summary — bounded & aggregate-only (no per-carton
     * hydration). Shows the 50 most recent batches that have cartons; each row
     * links to the filtered carton list instead of inline-expanding cartons.
     */
    private function batchSummary()
    {
        // Aggregate packed units + distinct cartons per batch, from contents.
        $packed = MasterCartonContent::selectRaw('batch_id, SUM(quantity) packed, COUNT(DISTINCT master_carton_id) cartons')
                    ->groupBy('batch_id')->get()->keyBy('batch_id');

        // Empty/unpacked standard cartons assigned to a batch.
        $emptyByBatch = MasterCarton::selectRaw('batch_id, COUNT(*) c')
                    ->whereNotNull('batch_id')->where('packed_quantity', 0)
                    ->groupBy('batch_id')->pluck('c', 'batch_id');

        // Recent batches that appear in cartons (bounded to 50).
        $batchIds = $packed->keys()
            ->merge($emptyByBatch->keys())
            ->unique()->sortDesc()->take(50)->values();

        if ($batchIds->isEmpty()) {
            return collect();
        }

        $batches = Batch::with('product')->whereIn('id', $batchIds)->get()->keyBy('id');

        return $batchIds->map(function ($bid) use ($packed, $emptyByBatch, $batches) {
            $row    = $packed->get($bid);
            $batch  = $batches->get($bid);
            $units  = (int) ($row->packed ?? 0);
            $total  = $batch?->total_quantity ?? 0;

            return [
                'batch'             => $batch,
                'cartons'           => (int) ($row->cartons ?? 0) + (int) ($emptyByBatch[$bid] ?? 0),
                'remaining_cartons' => (int) ($emptyByBatch[$bid] ?? 0),
                'packed'            => $units,
                'total'             => $total,
                'unpacked'          => max(0, $total - $units),
            ];
        })->values();
    }

    // ── Generate cartons (standard for a batch, or generic) ───────────────

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $type = $request->input('carton_type') === 'generic' ? 'generic' : 'standard';

        $rules = [
            'capacity'     => 'required|integer|min:1',
            'carton_count' => 'required|integer|min:1|max:' . self::MAX_CARTONS,
            'label'        => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:2000',
        ];
        if ($type === 'standard') {
            $rules['product_id'] = 'required|exists:products,id';
            $rules['batch_id']   = 'required|exists:batches,id';
        }
        $data = $request->validate($rules, [
            'capacity.required'     => 'Enter products per master carton.',
            'carton_count.required' => 'Enter the number of master cartons.',
            'product_id.required'   => 'Select a product (or switch to a generic carton).',
            'batch_id.required'     => 'Select a batch (or switch to a generic carton).',
        ]);

        $productId = null;
        $batchId   = null;

        if ($type === 'standard') {
            $batch = Batch::findOrFail((int) $data['batch_id']);
            if ((int) $batch->product_id !== (int) $data['product_id']) {
                return $this->fail($request, 'batch_id', 'The selected batch does not belong to the selected product.');
            }
            $productId = $batch->product_id;
            $batchId   = $batch->id;
        }

        $capacity = (int) $data['capacity'];
        $count    = (int) $data['carton_count'];

        DB::transaction(function () use ($type, $productId, $batchId, $capacity, $count, $data) {
            $last     = MasterCarton::withTrashed()->orderByDesc('id')->value('carton_number');
            $startSeq = $last ? ((int) substr($last, 3) + 1) : 1;

            $usedQr = MasterCarton::withTrashed()->pluck('qr_code')->flip();
            $now    = now();
            $rows   = [];

            for ($i = 0; $i < $count; $i++) {
                do {
                    $qr = Str::upper(Str::random(12));
                } while ($usedQr->has($qr));
                $usedQr->put($qr, true);

                $rows[] = [
                    'product_id'      => $productId,
                    'batch_id'        => $batchId,
                    'carton_number'   => 'MC-' . str_pad((string) ($startSeq + $i), 6, '0', STR_PAD_LEFT),
                    'qr_code'         => $qr,
                    'carton_type'     => $type,
                    'label'           => $data['label'] ?? null,
                    'capacity'        => $capacity,
                    'packed_quantity' => 0,
                    'status'          => 'created',
                    'notes'           => $data['notes'] ?? null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            foreach (array_chunk($rows, 2000) as $chunk) {
                MasterCarton::insert($chunk);
            }
        });

        $this->forgetCaches();
        $what    = $type === 'generic' ? 'generic master carton(s)' : "master carton(s) for batch";
        $message = "Generated {$count} {$what} of {$capacity} units each.";

        if ($request->wantsJson()) {
            session()->flash('success', $message);
            return response()->json(['success' => true, 'message' => $message, 'redirect' => route('master-cartons')]);
        }
        return redirect()->route('master-cartons')->with('success', $message);
    }

    // ── Packing: add a content segment to a carton ────────────────────────

    public function addContent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'carton_id'    => 'required|exists:master_cartons,id',
            'product_id'   => 'required|exists:products,id',
            'batch_id'     => 'required|exists:batches,id',
            'serial_start' => 'required|integer|min:1',
            'serial_end'   => 'required|integer|min:1|gte:serial_start',
        ], [
            'serial_end.gte' => 'Ending serial must be ≥ starting serial.',
        ]);

        $carton = MasterCarton::with('contents')->findOrFail((int) $data['carton_id']);
        $batch  = Batch::findOrFail((int) $data['batch_id']);
        $start  = (int) $data['serial_start'];
        $end    = (int) $data['serial_end'];
        $qty    = $end - $start + 1;

        if (in_array($carton->status, ['dispatched', 'received'], true)) {
            return $this->failJson('carton_id', "Carton {$carton->carton_number} has already been {$carton->status}; it can no longer be packed.");
        }
        if ((int) $batch->product_id !== (int) $data['product_id']) {
            return $this->failJson('batch_id', 'The selected batch does not belong to the selected product.');
        }
        // Capacity (running)
        if ($carton->packed_quantity + $qty > $carton->capacity) {
            return $this->failJson('serial_end', "Adding {$qty} units exceeds the carton's remaining capacity ({$carton->remaining_capacity}).");
        }
        // Serials exist in batch
        $existing = BatchUnit::where('batch_id', $batch->id)
                        ->whereBetween('serial_number', [$start, $end])
                        ->distinct()->count('serial_number');
        if ($existing < $qty) {
            return $this->failJson('serial_start', "Serials {$start}–{$end} are not fully available in batch '{$batch->brn}'.");
        }
        // No overlap with the same batch's serials already packed anywhere
        $overlap = MasterCartonContent::with('carton')
                        ->where('batch_id', $batch->id)
                        ->where('serial_start', '<=', $end)
                        ->where('serial_end', '>=', $start)
                        ->first();
        if ($overlap) {
            return $this->failJson('serial_start', "Serials overlap with carton {$overlap->carton?->carton_number} ({$overlap->serial_range}) for this batch.");
        }

        DB::transaction(function () use ($carton, $batch, $start, $end, $qty) {
            MasterCartonContent::create([
                'master_carton_id' => $carton->id,
                'product_id'       => $batch->product_id,
                'batch_id'         => $batch->id,
                'serial_start'     => $start,
                'serial_end'       => $end,
                'quantity'         => $qty,
            ]);

            BatchUnit::where('batch_id', $batch->id)
                ->whereBetween('serial_number', [$start, $end])
                ->where('status', 'generated')
                ->update(['status' => 'packed']);

            $carton->recomputeFromContents();
            $carton->save();
        });

        $this->forgetCaches();
        return response()->json([
            'success'  => true,
            'message'  => "Added serials {$start}–{$end} ({$qty} units) to {$carton->carton_number}.",
            'carton'   => $this->cartonPayload($carton->fresh('contents')),
            'contents' => $this->contentsPayload($carton->fresh(['contents.product', 'contents.batch'])),
        ]);
    }

    // ── Packing: remove a content segment ─────────────────────────────────

    public function removeContent(MasterCartonContent $content): JsonResponse
    {
        $carton = $content->carton()->with('contents')->first();

        if ($carton && in_array($carton->status, ['dispatched', 'received'], true)) {
            return $this->failJson('content', "Carton {$carton->carton_number} has already been {$carton->status}; its contents are locked.");
        }

        DB::transaction(function () use ($content, $carton) {
            // Free the units that were packed by this segment.
            BatchUnit::where('batch_id', $content->batch_id)
                ->whereBetween('serial_number', [$content->serial_start, $content->serial_end])
                ->where('status', 'packed')
                ->update(['status' => 'generated']);

            $content->delete();

            if ($carton) {
                $carton->recomputeFromContents();
                $carton->save();
            }
        });

        $this->forgetCaches();
        return response()->json([
            'success'  => true,
            'message'  => 'Segment removed.',
            'carton'   => $this->cartonPayload($carton?->fresh('contents')),
            'contents' => $this->contentsPayload($carton?->fresh(['contents.product', 'contents.batch'])),
        ]);
    }

    // ── JSON: cartons available for packing (have free capacity) ──────────

    public function packingCartons(Request $request): JsonResponse
    {
        $query = MasterCarton::whereColumn('packed_quantity', '<', 'capacity')
                    ->whereNotIn('status', ['dispatched', 'received']);

        // Bounded fetch — filter by a single carton, a batch, or generic type,
        // optionally narrowed by a carton-number prefix; never the whole table.
        if ($id = $request->query('id')) {
            $query->where('id', $id);
        } elseif ($request->query('type') === 'generic') {
            $query->where('carton_type', 'generic');
        } elseif ($batchId = $request->query('batch_id')) {
            $query->where('batch_id', $batchId);
        }
        if (($q = trim((string) $request->query('q', ''))) !== '') {
            $query->where('carton_number', 'like', $q . '%');
        }

        $cartons = $query->orderBy('carton_number')
                    ->limit(25)
                    ->get(['id', 'carton_number', 'carton_type', 'capacity', 'packed_quantity', 'batch_id', 'product_id']);

        return response()->json($cartons->map(fn ($c) => [
            'id'             => $c->id,
            'carton_number'  => $c->carton_number,
            'carton_type'    => $c->carton_type,
            'capacity'       => $c->capacity,
            'packed'         => $c->packed_quantity,
            'remaining'      => $c->remaining_capacity,
            'batch_id'       => $c->batch_id,
            'product_id'     => $c->product_id,
            'label'          => $c->carton_number . ' · ' . $c->remaining_capacity . '/' . $c->capacity . ' free'
                                . ($c->carton_type === 'generic' ? ' · generic' : ''),
        ]));
    }

    /** JSON: a carton's current contents + remaining capacity (pack modal). */
    public function cartonContents(MasterCarton $masterCarton): JsonResponse
    {
        return response()->json([
            'carton'   => $this->cartonPayload($masterCarton),
            'contents' => $this->contentsPayload($masterCarton->load(['contents.product', 'contents.batch'])),
        ]);
    }

    /**
     * JSON: next free serial for a batch (for auto-filling the start field).
     * = highest serial already packed into any carton for this batch + 1,
     * or the batch's lowest unit serial when nothing is packed yet.
     */
    public function batchPackInfo(Batch $batch): JsonResponse
    {
        $packedMax = MasterCartonContent::where('batch_id', $batch->id)->max('serial_end');
        $minSerial = BatchUnit::where('batch_id', $batch->id)->min('serial_number');
        $maxSerial = BatchUnit::where('batch_id', $batch->id)->max('serial_number');

        return response()->json([
            'brn'         => $batch->brn,
            'next_serial' => $packedMax ? $packedMax + 1 : ($minSerial ?? 1),
            'min_serial'  => $minSerial ?? 1,
            'max_serial'  => $maxSerial ?? 0,
        ]);
    }

    // ── JSON: single carton (view modal) ──────────────────────────────────

    public function show(MasterCarton $masterCarton): JsonResponse
    {
        $masterCarton->load(['product', 'batch', 'scans', 'contents.product', 'contents.batch']);

        return response()->json([
            ...$masterCarton->toArray(),
            'is_mixed'           => $masterCarton->is_mixed,
            'products_summary'   => $masterCarton->products_summary,
            'batches_summary'    => $masterCarton->batches_summary,
            'serial_range'       => $masterCarton->serial_range,
            'remaining_capacity' => $masterCarton->remaining_capacity,
            'status_label'       => $masterCarton->status_label,
            'status_badge_class' => $masterCarton->status_badge_class,
            'scan_url'           => route('carton.scan', $masterCarton->qr_code),
            'contents'           => $this->contentsPayload($masterCarton),
            'scans'              => $masterCarton->scans->map(fn ($s) => [
                'event' => $s->event, 'event_label' => $s->event_label, 'event_icon' => $s->event_icon,
                'performed_by' => $s->performed_by, 'location' => $s->location, 'note' => $s->note,
                'at' => $s->created_at?->toIso8601String(),
            ]),
        ]);
    }

    // ── Movement: dispatch / receive ──────────────────────────────────────

    public function move(Request $request, MasterCarton $masterCarton): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'event'    => 'required|in:dispatched,received',
            'location' => 'nullable|string|max:255',
            'note'     => 'nullable|string|max:1000',
        ]);

        if (!$masterCarton->is_packed) {
            return $this->fail($request, 'event', "Carton {$masterCarton->carton_number} has not been packed yet. Pack serials into it before dispatch or receiving.");
        }
        if ($data['event'] === 'received' && !$masterCarton->dispatched_at) {
            return $this->fail($request, 'event', "Carton {$masterCarton->carton_number} must be dispatched from the factory before it can be received at the depot.");
        }

        $masterCarton->update([
            'status'        => $data['event'],
            'dispatched_at' => $data['event'] === 'dispatched' ? now() : $masterCarton->dispatched_at,
            'received_at'   => $data['event'] === 'received' ? now() : $masterCarton->received_at,
        ]);

        MasterCartonScan::create([
            'master_carton_id' => $masterCarton->id,
            'event'            => $data['event'],
            'performed_by'     => optional(auth()->user())->name ?? 'system',
            'location'         => $data['location'] ?? null,
            'note'             => $data['note'] ?? null,
        ]);

        $this->forgetCaches();
        $label   = $data['event'] === 'dispatched' ? 'dispatched from factory' : 'received at depot';
        $message = "Carton {$masterCarton->carton_number} marked as {$label}.";
        if ($request->wantsJson()) {
            session()->flash('success', $message);
            return response()->json(['success' => true, 'message' => $message, 'redirect' => route('master-cartons')]);
        }
        return redirect()->route('master-cartons')->with('success', $message);
    }

    // ── Printable labels & PDF (any carton set: all / generic / batch) ────

    /** Browser print view; filterable by product/batch/status/type. */
    public function labels(Request $request): View
    {
        $filters = $request->only(['product_id', 'batch_id', 'status', 'type', 'search', 'fill', 'date_from', 'date_to', 'ids']);
        $cartons = $this->labelsQuery($filters)->paginate(60)->withQueryString();
        $title   = $this->labelsTitle($filters);

        return view('carton-labels', compact('cartons', 'title', 'filters'));
    }

    /** Downloadable PDF of carton labels; same filters as the print view. */
    public function labelsPdf(Request $request)
    {
        $filters = $request->only(['product_id', 'batch_id', 'status', 'type', 'search', 'fill', 'date_from', 'date_to', 'ids']);
        $count   = $this->labelsQuery($filters)->count();
        abort_if($count === 0, 404, 'No cartons match the selected filters.');
        abort_if($count > 1000, 422, "Too many cartons ({$count}) for a single PDF. Narrow the filter (product / batch / status / date) or select specific rows — max 1000 per PDF.");

        $cartons = $this->labelsQuery($filters)->get();

        $title = $this->labelsTitle($filters);
        $name  = Str::slug($title ?: 'master_carton', '_') . '_labels';

        $pdf = Pdf::loadView('exports.carton-labels', compact('cartons', 'title'))
                    ->setPaper('a4', 'portrait')
                    ->setOption('isRemoteEnabled', true);
        return $pdf->download("{$name}.pdf");
    }

    private function labelsQuery(array $filters)
    {
        $q = MasterCarton::with(['product', 'batch', 'contents.product', 'contents.batch'])
                ->filter($filters)
                ->orderBy('carton_number');

        if (($filters['type'] ?? '') === 'generic') {
            $q->where('carton_type', 'generic');
        } elseif (($filters['type'] ?? '') === 'standard') {
            $q->where('carton_type', 'standard');
        }
        return $q;
    }

    private function labelsTitle(array $filters): string
    {
        if (!empty($filters['ids'])) {
            return 'Selected Master Cartons';
        }
        if (($filters['type'] ?? '') === 'generic') {
            return 'Generic Master Cartons';
        }
        if (!empty($filters['batch_id'])) {
            $b = Batch::with('product')->find($filters['batch_id']);
            return trim(($b?->product?->name ?? 'Product') . ' — ' . ($b?->brn ?? ''));
        }
        if (!empty($filters['product_id'])) {
            return Product::find($filters['product_id'])?->name ?? 'Master Cartons';
        }
        return 'All Master Cartons';
    }

    // ── Public QR scan page ───────────────────────────────────────────────

    public function scan(string $qr): View
    {
        $carton = MasterCarton::with(['product', 'batch', 'contents.product', 'contents.batch'])
                    ->where('qr_code', $qr)->first();

        if ($carton) {
            MasterCartonScan::create([
                'master_carton_id' => $carton->id,
                'event'            => 'scanned',
                'performed_by'     => 'public',
                'note'             => 'QR scan via carton label.',
            ]);
        }

        return view('carton-verify', compact('carton', 'qr'));
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(MasterCarton $masterCarton): RedirectResponse
    {
        $num = $masterCarton->carton_number;

        DB::transaction(function () use ($masterCarton) {
            // Free any packed units before removing the carton + its contents.
            foreach ($masterCarton->contents as $content) {
                BatchUnit::where('batch_id', $content->batch_id)
                    ->whereBetween('serial_number', [$content->serial_start, $content->serial_end])
                    ->where('status', 'packed')
                    ->update(['status' => 'generated']);
            }
            $masterCarton->contents()->delete();
            $masterCarton->delete();
        });

        $this->forgetCaches();
        return redirect()->route('master-cartons')->with('success', "Carton {$num} removed.");
    }

    // ── Payload helpers ───────────────────────────────────────────────────

    private function cartonPayload(?MasterCarton $c): ?array
    {
        if (!$c) return null;
        return [
            'id'              => $c->id,
            'carton_number'   => $c->carton_number,
            'capacity'        => $c->capacity,
            'packed_quantity' => $c->packed_quantity,
            'remaining'       => $c->remaining_capacity,
            'status'          => $c->status,
            'is_mixed'        => $c->is_mixed,
        ];
    }

    private function contentsPayload(?MasterCarton $c): array
    {
        if (!$c) return [];
        return $c->contents->map(fn ($x) => [
            'id'           => $x->id,
            'product_name' => $x->product?->name,
            'brn'          => $x->batch?->brn,
            'batch_number' => $x->batch?->batch_number,
            'serial_start' => $x->serial_start,
            'serial_end'   => $x->serial_end,
            'quantity'     => $x->quantity,
        ])->values()->all();
    }

    private function fail(Request $request, string $field, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'errors' => [$field => [$message]]], 422);
        }
        return back()->withInput()->withErrors([$field => $message]);
    }

    private function failJson(string $field, string $message): JsonResponse
    {
        return response()->json(['success' => false, 'errors' => [$field => [$message]]], 422);
    }
}
