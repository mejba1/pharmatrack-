<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchExtension;
use App\Models\BatchUnit;
use App\Models\BatchUnitLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Partial Batch Quantity (Batch Quantity Extension).
 * Adds additional serialized units to an existing batch after its creation,
 * with a choice of serial-number starting point.
 */
class PartialBatchController extends Controller
{
    /** Safety cap so a typo can't try to insert millions of rows in one go. */
    private const MAX_UNITS = 100000;

    // ── Form page ─────────────────────────────────────────────────────────

    public function index(): View
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'prn']);
        $recent   = BatchExtension::with(['batch', 'product'])->latest()->limit(10)->get();

        return view('partial-batches', compact('products', 'recent'));
    }

    // ── AJAX: batches for the selected product ────────────────────────────

    public function batches(Product $product): JsonResponse
    {
        $batches = $product->batches()
            ->orderByDesc('id')
            ->get(['id', 'brn', 'batch_number', 'lot_number'])
            ->map(fn ($b) => [
                'id'           => $b->id,
                'brn'          => $b->brn,
                'batch_number' => $b->batch_number,
                'lot_number'   => $b->lot_number,
                'label'        => $b->brn . ' — ' . $b->batch_number . ($b->lot_number ? ' / ' . $b->lot_number : ''),
            ]);

        return response()->json($batches);
    }

    // ── AJAX: full info for the selected batch ────────────────────────────

    public function batchInfo(Batch $batch): JsonResponse
    {
        $batch->load('product');

        $generatedUnits = $batch->units()->count();

        return response()->json([
            'id'                 => $batch->id,
            'brn'                => $batch->brn,
            'batch_number'       => $batch->batch_number,
            'lot_number'         => $batch->lot_number,
            'product_name'       => $batch->product?->name,
            'product_prn'        => $batch->product?->prn,
            'manufacture_date'   => optional($batch->manufacture_date)->toDateString(),
            'expiry_date'        => optional($batch->expiry_date)->toDateString(),
            'quantity_produced'  => $batch->quantity_produced,
            'quantity_extended'  => $batch->quantity_extended,
            'total_quantity'     => $batch->total_quantity,
            'quantity_available' => $batch->quantity_available,
            'generated_units'    => $generatedUnits,
            'next_serial'        => $batch->total_quantity + 1, // "continue from" starting point
            'suggested_ref'      => Batch::generatePartialRef($batch->product_id),
        ]);
    }

    // ── Store: generate the additional units ──────────────────────────────

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id'          => 'required|exists:products,id',
            'batch_id'            => 'required|exists:batches,id',
            'additional_quantity' => 'required|integer|min:1|max:' . self::MAX_UNITS,
            'serial_mode'         => 'required|in:continue,restart',
            'override_dates'      => 'nullable|boolean',
            'manufacture_date'    => 'nullable|required_if:override_dates,1|date',
            'expiry_date'         => 'nullable|required_if:override_dates,1|date|after:manufacture_date',
            'notes'               => 'nullable|string|max:2000',
        ], [
            'product_id.required'          => 'Please select a product.',
            'batch_id.required'            => 'Please select an existing batch.',
            'additional_quantity.required' => 'Enter the additional quantity to generate.',
            'additional_quantity.min'      => 'Additional quantity must be at least 1.',
            'additional_quantity.max'      => 'Additional quantity is too large (max ' . number_format(self::MAX_UNITS) . ').',
            'manufacture_date.required_if' => 'Enter the manufacturing date for this partial batch.',
            'expiry_date.required_if'      => 'Enter the expiry date for this partial batch.',
            'expiry_date.after'            => 'Partial batch expiry date must be after its manufacturing date.',
        ]);

        $batch = Batch::with('product')->findOrFail((int) $data['batch_id']);

        // Guard: the batch must belong to the selected product.
        if ((int) $batch->product_id !== (int) $data['product_id']) {
            return $this->fail($request, 'batch_id', 'The selected batch does not belong to the selected product.');
        }

        $quantity = (int) $data['additional_quantity'];
        $mode     = $data['serial_mode'];
        $start    = $mode === 'restart' ? 1 : $batch->total_quantity + 1;
        $end      = $start + $quantity - 1;

        // Effective dates: use the per-partial overrides when supplied, otherwise
        // inherit the parent batch's manufacturing & expiry dates.
        $override = (bool) ($data['override_dates'] ?? false);
        $mfgDate  = $override ? $data['manufacture_date'] : optional($batch->manufacture_date)->toDateString();
        $expDate  = $override ? $data['expiry_date']      : optional($batch->expiry_date)->toDateString();

        $result = DB::transaction(function () use ($batch, $data, $quantity, $mode, $start, $end, $mfgDate, $expDate) {
            $partialRef = Batch::generatePartialRef($batch->product_id);

            $extension = BatchExtension::create([
                'batch_id'            => $batch->id,
                'product_id'          => $batch->product_id,
                'partial_ref'         => $partialRef,
                'additional_quantity' => $quantity,
                'serial_mode'         => $mode,
                'serial_start'        => $start,
                'serial_end'          => $end,
                'manufacture_date'    => $mfgDate,
                'expiry_date'         => $expDate,
                'performed_by'        => optional(auth()->user())->name,
                'notes'               => $data['notes'] ?? null,
            ]);

            $this->generateUnits($batch, $partialRef, $start, $quantity);

            // Roll the new units into the batch totals.
            $batch->increment('quantity_extended', $quantity);
            $batch->increment('quantity_available', $quantity);

            BatchUnitLog::create([
                'batch_id'     => $batch->id,
                'event'        => 'units_extended',
                'to_status'    => 'generated',
                'quantity'     => $quantity,
                'note'         => "Partial batch {$partialRef}: generated {$quantity} units "
                                . "(serials {$start}–{$end}, " . ($mode === 'restart' ? 'restarted from 1' : 'continued') . ").",
                'performed_by' => optional(auth()->user())->name,
            ]);

            return $extension;
        });

        $message = "Partial batch '{$result->partial_ref}' added "
            . number_format($quantity) . " units (serials {$start}–{$end}) to batch '{$batch->brn}'. "
            . "New total quantity: " . number_format($batch->fresh()->total_quantity) . ".";

        if ($request->wantsJson()) {
            session()->flash('success', $message);
            return response()->json([
                'success'  => true,
                'message'  => $message,
                'redirect' => route('partial-batches'),
            ]);
        }

        return redirect()->route('partial-batches')->with('success', $message);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Create $count serialized units for $batch tagged with $partialRef,
     * numbered $startSerial..($startSerial + $count - 1). Each unit gets a
     * globally-unique 10-digit label and a 10-char uppercase secret code.
     */
    private function generateUnits(Batch $batch, string $partialRef, int $startSerial, int $count): void
    {
        $usedLabels = BatchUnit::pluck('unique_number')->flip();

        $now  = now();
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            do {
                $label = (string) random_int(1000000000, 9999999999); // 10-digit
            } while ($usedLabels->has($label));
            $usedLabels->put($label, true);

            $rows[] = [
                'batch_id'          => $batch->id,
                'partial_batch_ref' => $partialRef,
                'serial_number'     => $startSerial + $i,
                'secret_code'       => Str::upper(Str::random(10)),
                'unique_number'     => $label,
                'status'            => 'generated',
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            if (count($rows) >= 2000) {
                BatchUnit::insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            BatchUnit::insert($rows);
        }
    }

    private function fail(Request $request, string $field, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'errors' => [$field => [$message]]], 422);
        }

        return back()->withInput()->withErrors([$field => $message]);
    }
}
