<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchUnit;
use App\Models\BatchUnitLog;
use App\Models\Product;
use App\Models\Country;
use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class BatchController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'qc_status', 'status', 'expiry', 'product_id']);

        $batches = Batch::with('product')
                    ->withCount('units')
                    ->filter($filters)
                    ->latest()
                    ->paginate(15)
                    ->withQueryString();

        $stats = [
            'total'     => Batch::count(),
            'released'  => Batch::where('qc_status', 'released')->count(),
            'expiring'  => Batch::whereDate('expiry_date', '>=', now())
                                ->whereDate('expiry_date', '<=', now()->addDays(90))->count(),
            'recalled'  => Batch::where('status', 'recalled')->count(),
        ];

        $products  = Product::orderBy('name')->get(['id', 'name', 'prn']);
        $countries = Country::orderBy('name')->get(['code', 'name']);

        return view('batches', compact('batches', 'stats', 'filters', 'products', 'countries'));
    }

    // ── Store ─────────────────────────────────────────────────────────────

    public function store(StoreBatchRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $data['brn']                = Batch::generateBrn((int) $data['product_id']);
        $data['quantity_available'] = $data['quantity_available'] ?? $data['quantity_produced'];
        $data['qc_status']          ??= 'pending';
        $data['status']             ??= 'active';

        unset($data['coa']);

        $batch = Batch::create($data);

        if ($request->hasFile('coa')) {
            $batch->update(['coa_document_path' => $this->saveCoa($batch, $request->file('coa'))]);
        }

        // Auto-generate one serialized unit per produced item (serial 1..quantity)
        $sync = $this->syncUnits($batch);
        if ($sync['added']) {
            $this->logTrace($batch, 'units_generated', [
                'quantity'  => $sync['added'],
                'to_status' => 'generated',
                'note'      => "Generated {$sync['added']} units on batch creation.",
            ]);
        }

        $message = "Batch '{$batch->brn}' created with " . number_format($sync['added']) . " serialized units.";

        if ($request->wantsJson()) {
            session()->flash('success', $message);
            return response()->json([
                'success'  => true,
                'message'  => $message,
                'redirect' => route('batches'),
            ]);
        }

        return redirect()->route('batches')->with('success', $message);
    }

    // ── Show (JSON for AJAX modals) ───────────────────────────────────────

    public function show(Batch $batch): JsonResponse
    {
        $batch->load('product');

        return response()->json([
            ...$batch->toArray(),
            'product_name'     => $batch->product?->name,
            'product_prn'      => $batch->product?->prn,
            'qc_status_label'  => $batch->qc_status_label,
            'qc_badge_class'   => $batch->qc_badge_class,
            'status_label'     => $batch->status_label,
            'status_badge_class' => $batch->status_badge_class,
            'days_to_expiry'   => $batch->days_to_expiry,
            'coa_url'          => $batch->coa_url,
            'coa_name'         => $batch->coa_name,
            'units_count'      => $batch->units()->count(),
        ]);
    }

    // ── Units (serialized track-and-trace) ────────────────────────────────

    public function units(Request $request, Batch $batch): View
    {
        $batch->load('product');

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 50, 100, 500, 2000], true)) {
            $perPage = 10;
        }

        $sortable = ['serial_number', 'unique_number', 'secret_code', 'status', 'created_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'serial_number';
        $dir  = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $units = $batch->units()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $s = $request->input('q');
                $q->where(fn ($w) => $w->where('unique_number', 'like', "%{$s}%")
                                       ->orWhere('secret_code', 'like', "%{$s}%")
                                       ->orWhere('serial_number', $s));
            })
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();

        $statusCounts = $batch->units()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('batch-units', compact('batch', 'units', 'statusCounts'));
    }

    public function logs(Batch $batch): View
    {
        $batch->load('product');
        $logs = $batch->unitLogs()->with('unit')->paginate(50);

        return view('batch-logs', compact('batch', 'logs'));
    }

    /** Printable sheet of QR labels for all units in a batch. */
    public function labels(Batch $batch): View
    {
        $batch->load('product');
        $units = $batch->units()->orderBy('serial_number')->paginate(120);

        return view('batch-labels', compact('batch', 'units'));
    }

    /**
     * Export a batch's unit codes as TXT / CSV(Excel) / PDF.
     * field  = secret_code | unique_number
     * format = txt | excel | pdf
     */
    public function export(Request $request, Batch $batch)
    {
        $batch->load('product');

        $field  = $request->input('field') === 'unique_number' ? 'unique_number' : 'secret_code';
        $format = in_array($request->input('format'), ['txt', 'excel', 'pdf']) ? $request->input('format') : 'txt';

        $rows = $batch->units()->orderBy('serial_number')->get(['serial_number', $field]);

        $label    = $field === 'unique_number' ? 'Unique Number' : 'Secret Code';
        $product  = Str::slug($batch->product?->name ?? 'product', '_');
        $batchTag = Str::slug($batch->batch_number, '_');
        $base     = "{$product}_{$batchTag}_{$field}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.batch-codes', compact('batch', 'rows', 'field', 'label'));
            return $pdf->download("{$base}.pdf");
        }

        if ($format === 'excel') {
            $csv  = "\xEF\xBB\xBF";                 // UTF-8 BOM so Excel detects encoding
            $csv .= "Serial,{$label}\r\n";
            foreach ($rows as $r) {
                $csv .= $r->serial_number . ',' . $r->{$field} . "\r\n";
            }
            return response($csv, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$base}.csv\"",
            ]);
        }

        // txt — one code per line
        $txt = $rows->pluck($field)->implode("\n");
        return response($txt, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$base}.txt\"",
        ]);
    }

    /** Public product-verification page reached from a unit's QR code. */
    public function verify(string $code): View
    {
        $unit = BatchUnit::with('batch.product')->where('secret_code', $code)->first();

        if ($unit) {
            BatchUnitLog::create([
                'batch_id'      => $unit->batch_id,
                'batch_unit_id' => $unit->id,
                'event'         => 'scanned',
                'note'          => 'Verification scan via QR code.',
                'performed_by'  => 'public',
            ]);
        }

        return view('verify', compact('unit', 'code'));
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function update(UpdateBatchRequest $request, Batch $batch): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        unset($data['coa'], $data['remove_coa']);

        $data['quantity_available'] = $data['quantity_available'] ?? $batch->quantity_available;

        $batch->update($data);

        // COA — remove and/or replace
        if ($request->boolean('remove_coa') && $batch->coa_document_path) {
            Storage::disk('public')->delete($batch->coa_document_path);
            $batch->update(['coa_document_path' => null]);
        }
        if ($request->hasFile('coa')) {
            if ($batch->coa_document_path) {
                Storage::disk('public')->delete($batch->coa_document_path);
            }
            $batch->update(['coa_document_path' => $this->saveCoa($batch, $request->file('coa'))]);
        }

        // Reconcile serialized units with the (possibly changed) quantity
        $sync = $this->syncUnits($batch);
        if ($sync['added']) {
            $this->logTrace($batch, 'units_generated', [
                'quantity'  => $sync['added'],
                'to_status' => 'generated',
                'note'      => "Topped up {$sync['added']} units after quantity increase.",
            ]);
        }
        if ($sync['removed']) {
            $this->logTrace($batch, 'units_removed', [
                'quantity'    => $sync['removed'],
                'from_status' => 'generated',
                'note'        => "Removed {$sync['removed']} generated units from the end after quantity decrease.",
            ]);
        }

        $message = "Batch '{$batch->brn}' updated successfully.";
        if ($sync['removed']) {
            $message .= " Removed " . number_format($sync['removed']) . " unused units.";
        }
        if ($sync['blocked']) {
            $message .= " Note: " . number_format($sync['blocked']) . " unit(s) beyond the new quantity could not be removed because they are no longer in 'generated' status.";
        }

        if ($request->wantsJson()) {
            session()->flash('success', $message);
            return response()->json([
                'success'  => true,
                'message'  => $message,
                'redirect' => route('batches'),
            ]);
        }

        return redirect()->route('batches')->with('success', $message);
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(Batch $batch): RedirectResponse
    {
        $brn = $batch->brn;
        if ($batch->coa_document_path) {
            Storage::disk('public')->delete($batch->coa_document_path);
        }
        $batch->delete();

        return redirect()->route('batches')->with('success', "Batch '{$brn}' has been removed.");
    }

    // ── Private Helpers ───────────────────────────────────────────────────

    private function saveCoa(Batch $batch, UploadedFile $file): string
    {
        $slug     = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = time() . '_' . uniqid() . '_' . ($slug ?: 'coa') . '.pdf';

        return $file->storeAs("batches/{$batch->id}/coa", $filename, 'public');
    }

    /** Safety cap so a typo in the quantity can't try to insert millions of rows. */
    private const MAX_UNITS = 100000;

    /**
     * Reconcile a batch's serialized units with its produced quantity.
     *  - quantity increased → append new units from the end
     *  - quantity decreased → remove trailing units, but ONLY those still in
     *    'generated' status (printed/packed/etc. units can't be un-produced)
     * Returns ['added' => int, 'removed' => int, 'blocked' => int].
     */
    private function syncUnits(Batch $batch): array
    {
        $target = min((int) $batch->quantity_produced, self::MAX_UNITS);

        // Remove from the end (serials beyond target) — generated-only
        $removed = $batch->units()
            ->where('serial_number', '>', $target)
            ->where('status', 'generated')
            ->delete();

        // Anything still beyond target is locked (printed/packed/etc.)
        $blocked = $batch->units()->where('serial_number', '>', $target)->count();

        // Fill any missing serials within 1..target (handles fresh batches and gaps)
        $added = $this->fillUnits($batch, $target);

        return compact('added', 'removed', 'blocked');
    }

    /**
     * Create serialized units for any missing serials in 1..target. Each unit
     * gets a 10-digit numeric label (unique_number, e.g. 8901481061) and a
     * 10-char alphanumeric secret code (QR/verify, e.g. QRWT4RGl6K).
     * Returns the count added.
     */
    private function fillUnits(Batch $batch, int $target): int
    {
        if ($target < 1) {
            return 0;
        }

        $existing = $batch->units()->where('serial_number', '<=', $target)
                          ->pluck('serial_number')->flip();
        if ($existing->count() >= $target) {
            return 0;
        }

        // Existing labels (across all batches) to guarantee uniqueness
        $usedLabels = BatchUnit::pluck('unique_number')->flip();

        $now   = now();
        $rows  = [];
        $added = 0;

        for ($i = 1; $i <= $target; $i++) {
            if ($existing->has($i)) {
                continue;
            }
            do {
                $label = (string) random_int(1000000000, 9999999999); // 10-digit
            } while ($usedLabels->has($label));
            $usedLabels->put($label, true);

            $rows[] = [
                'batch_id'      => $batch->id,
                'serial_number' => $i,
                'secret_code'   => Str::upper(Str::random(10)),   // uppercase alphanumeric, e.g. QRWT4RGL6K
                'unique_number' => $label,
                'status'        => 'generated',
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            if (count($rows) >= 2000) {
                BatchUnit::insert($rows);
                $added += count($rows);
                $rows = [];
            }
        }

        if ($rows) {
            BatchUnit::insert($rows);
            $added += count($rows);
        }

        return $added;
    }

    /** Append a trace-log entry for a batch event. */
    private function logTrace(Batch $batch, string $event, array $attrs = []): void
    {
        BatchUnitLog::create(array_merge([
            'batch_id'     => $batch->id,
            'event'        => $event,
            'performed_by' => optional($this->user())->name,
        ], $attrs));
    }

    private function user()
    {
        return auth()->user();
    }
}
