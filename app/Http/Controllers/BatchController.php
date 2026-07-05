<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchUnit;
use App\Models\BatchUnitLog;
use App\Models\Product;
use App\Models\Country;
use App\Models\CounterfeitReport;
use App\Models\ProductInfoRequest;
use App\Models\ProductRecall;
use App\Models\Setting;
use App\Models\VerificationLog;
use App\Models\VerificationPolicy;
use App\Mail\ProductInfoMail;
use Illuminate\Support\Facades\Mail;
use App\Services\DeviceParser;
use App\Services\GeoIpService;
use App\Services\RiskEngine;
use App\Services\ScanIntelligence;
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

        $extensions   = $batch->extensions()->get();
        $originalCount = $batch->units()->whereNull('partial_batch_ref')->count();

        return view('batch-units', compact('batch', 'units', 'statusCounts', 'extensions', 'originalCount'));
    }

    public function logs(Batch $batch): View
    {
        $batch->load('product');
        $logs = $batch->unitLogs()->with('unit')->paginate(50);

        return view('batch-logs', compact('batch', 'logs'));
    }

    /**
     * Quantity-composition tracking for a batch (JSON for the list modal):
     * the original batch creation followed by each partial batch extension,
     * with a running total so the build-up is easy to read.
     */
    public function tracking(Batch $batch): JsonResponse
    {
        return response()->json($this->buildTracking($batch));
    }

    /**
     * Export the quantity-tracking log (original batch + partial batches)
     * as TXT / CSV(Excel) / PDF.
     */
    public function trackingExport(Request $request, Batch $batch)
    {
        $data   = $this->buildTracking($batch);
        $format = in_array($request->input('format'), ['txt', 'excel', 'pdf']) ? $request->input('format') : 'txt';

        $batchTag = Str::slug($batch->batch_number, '_');
        $product  = Str::slug($batch->product?->name ?? 'product', '_');
        $base     = "{$product}_{$batchTag}_tracking";

        $cols = ['#', 'Type', 'Reference', 'Quantity', 'Serial Start', 'Serial End', 'Serial Mode',
                 'Mfg Date', 'Expiry Date', 'Running Total', 'Performed By', 'Created', 'Notes'];

        $rows = [];
        foreach ($data['entries'] as $i => $e) {
            $rows[] = [
                $i + 1,
                $e['label'],
                $e['reference'],
                $e['quantity'],
                $e['serial_start'],
                $e['serial_end'],
                $e['serial_mode'] ?? '—',
                $e['manufacture_date'] ?? '—',
                $e['expiry_date'] ?? '—',
                $e['running_total'],
                $e['performed_by'] ?? '—',
                $e['created_at'] ? \Illuminate\Support\Carbon::parse($e['created_at'])->format('Y-m-d H:i') : '—',
                $e['notes'] ?? '',
            ];
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.batch-tracking', compact('batch', 'data'));
            return $pdf->download("{$base}.pdf");
        }

        if ($format === 'excel') {
            $csv = "\xEF\xBB\xBF";                                   // UTF-8 BOM for Excel
            $csv .= implode(',', $cols) . "\r\n";
            foreach ($rows as $r) {
                $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $r)) . "\r\n";
            }
            return response($csv, 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$base}.csv\"",
            ]);
        }

        // txt — readable, aligned summary + one line per entry
        $txt  = "Quantity Tracking Log\r\n";
        $txt .= "Batch: {$data['brn']}  ·  Product: " . ($data['product_name'] ?? '—') . "\r\n";
        $txt .= "Original: {$data['quantity_produced']}  ·  Added via partials: {$data['quantity_extended']}  ·  Total: {$data['total_quantity']}\r\n";
        $txt .= str_repeat('-', 60) . "\r\n";
        foreach ($data['entries'] as $i => $e) {
            $sign  = $e['type'] === 'original' ? '' : '+';
            $txt  .= ($i + 1) . ". {$e['label']} [{$e['reference']}]  {$sign}{$e['quantity']} units"
                   . "  serials {$e['serial_start']}-{$e['serial_end']}  running total: {$e['running_total']}\r\n";
            $txt  .= "    Mfg " . ($e['manufacture_date'] ?? '—') . "  ·  Exp " . ($e['expiry_date'] ?? '—')
                   . ($e['serial_mode'] ? "  ·  " . $e['serial_mode'] : '') . "\r\n";
            if (!empty($e['notes'])) {
                $txt .= "    Notes: {$e['notes']}\r\n";
            }
        }
        return response($txt, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$base}.txt\"",
        ]);
    }

    /**
     * Export scope options for a batch (JSON): full batch, original-only, and
     * one entry per partial batch. Used by the Batch Downloads page.
     */
    public function exportScopes(Batch $batch): JsonResponse
    {
        $batch->load(['product', 'extensions' => fn ($q) => $q->orderBy('id')]);

        $total    = $batch->units()->count();
        $original = $batch->units()->whereNull('partial_batch_ref')->count();

        $scopes = [['value' => 'full', 'label' => 'Full batch — all units', 'count' => $total]];

        if ($batch->extensions->count()) {
            $scopes[] = ['value' => 'original', 'label' => 'Original units only', 'count' => $original];
            foreach ($batch->extensions as $ext) {
                $scopes[] = [
                    'value' => $ext->partial_ref,
                    'label' => $ext->partial_ref . ' — partial (serials ' . $ext->serial_start . '–' . $ext->serial_end . ')',
                    'count' => (int) $ext->additional_quantity,
                ];
            }
        }

        return response()->json([
            'brn'          => $batch->brn,
            'batch_number' => $batch->batch_number,
            'product_name' => $batch->product?->name,
            'total'        => $total,
            'has_partials' => $batch->extensions->isNotEmpty(),
            'scopes'       => $scopes,
        ]);
    }

    /** Build the quantity-composition tracking payload (original + extensions). */
    private function buildTracking(Batch $batch): array
    {
        $batch->load(['product', 'extensions' => fn ($q) => $q->orderBy('id')]);

        $running = 0;
        $entries = [];

        // 1) Original batch
        $running += (int) $batch->quantity_produced;
        $entries[] = [
            'type'             => 'original',
            'label'            => 'Original Batch',
            'reference'        => $batch->brn,
            'quantity'         => (int) $batch->quantity_produced,
            'serial_start'     => $batch->quantity_produced > 0 ? 1 : 0,
            'serial_end'       => (int) $batch->quantity_produced,
            'serial_mode'      => null,
            'manufacture_date' => optional($batch->manufacture_date)->toDateString(),
            'expiry_date'      => optional($batch->expiry_date)->toDateString(),
            'performed_by'     => null,
            'notes'            => null,
            'created_at'       => optional($batch->created_at)->toIso8601String(),
            'running_total'    => $running,
        ];

        // 2..n) Partial batch extensions
        foreach ($batch->extensions as $ext) {
            $running += (int) $ext->additional_quantity;
            $entries[] = [
                'type'             => 'partial',
                'label'            => 'Partial Batch',
                'reference'        => $ext->partial_ref,
                'quantity'         => (int) $ext->additional_quantity,
                'serial_start'     => (int) $ext->serial_start,
                'serial_end'       => (int) $ext->serial_end,
                'serial_mode'      => $ext->serial_mode,
                'manufacture_date' => optional($ext->manufacture_date)->toDateString(),
                'expiry_date'      => optional($ext->expiry_date)->toDateString(),
                'performed_by'     => $ext->performed_by,
                'notes'            => $ext->notes,
                'created_at'       => optional($ext->created_at)->toIso8601String(),
                'running_total'    => $running,
            ];
        }

        return [
            'brn'               => $batch->brn,
            'product_name'      => $batch->product?->name,
            'batch_number'      => $batch->batch_number,
            'quantity_produced' => (int) $batch->quantity_produced,
            'quantity_extended' => (int) $batch->quantity_extended,
            'total_quantity'    => $batch->total_quantity,
            'entries'           => $entries,
        ];
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

        // Scope: '' / 'full' = whole batch, 'original' = pre-extension units only,
        // or a specific Partial Batch Reference Number.
        $scope = (string) $request->input('partial_ref', '');

        $rows = $batch->units()
            ->when($scope === 'original', fn ($q) => $q->whereNull('partial_batch_ref'))
            ->when($scope !== '' && $scope !== 'full' && $scope !== 'original',
                   fn ($q) => $q->where('partial_batch_ref', $scope))
            ->orderBy('serial_number')
            ->get(['serial_number', $field]);

        $label    = $field === 'unique_number' ? 'Unique Number' : 'Secret Code';
        $product  = Str::slug($batch->product?->name ?? 'product', '_');
        $batchTag = Str::slug($batch->batch_number, '_');
        $scopeTag = match (true) {
            $scope === 'original'                          => '_original',
            $scope !== '' && $scope !== 'full'             => '_' . Str::slug($scope, '_'),
            default                                        => '',
        };
        $base     = "{$product}_{$batchTag}{$scopeTag}_{$field}";

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
    public function verify(
        string $code,
        Request $request,
        GeoIpService $geo,
        DeviceParser $device,
        RiskEngine $engine
    ): View {
        $unit = BatchUnit::with('batch.product.images')->where('secret_code', $code)->first();

        $verification = null;
        $recall       = null;
        $scanStats    = ['count' => 0, 'first' => null, 'last' => null, 'countries' => 0];

        $policy = null;
        $block  = $unit ? null : 'fake';   // unknown code → not genuine

        $cfg            = Setting::config();
        $alerts         = [];      // scan-intelligence customer alerts
        $certificate    = false;   // returning-scan certificate prompt
        $intendedRegion = null;    // region mismatch target

        if ($unit) {
            $policy = VerificationPolicy::resolveForUnit($unit);

            // Lightweight unit trace (unchanged) for the journey timeline.
            BatchUnitLog::create([
                'batch_id'      => $unit->batch_id,
                'batch_unit_id' => $unit->id,
                'event'         => 'scanned',
                'note'          => 'Verification scan via QR code.',
                'performed_by'  => 'public',
            ]);

            // ── Capture verification intelligence (geo + device + risk) ──
            $ip   = $request->ip();
            $g    = $geo->lookup($ip);
            $d    = $device->parse($request->userAgent());

            // Recall resolution is country-aware (country-scoped recalls only
            // fire for the matching scan country).
            $recall = ProductRecall::activeForUnit($unit, $g['country_code']);

            $expired  = $unit->batch?->expiry_date && $unit->batch->expiry_date->isPast();
            $blocked  = in_array($unit->status, ['blocked', 'inactive', 'expired'], true);

            $verification = VerificationLog::create([
                'verification_number' => VerificationLog::nextNumber(),
                'uuc_code'            => $code,
                'batch_unit_id'       => $unit->id,
                'batch_id'            => $unit->batch_id,
                'product_id'          => $unit->batch?->product_id,
                'result'              => 'genuine', // refined below
                'ip_address'          => $ip,
                'country'             => $g['country'],
                'country_code'        => $g['country_code'],
                'city'                => $g['city'],
                'region'              => $g['region'],
                'isp'                 => $g['isp'],
                'latitude'            => $g['lat'],
                'longitude'           => $g['lon'],
                'is_proxy'            => $g['is_proxy'],
                'browser'             => $d['browser'],
                'os'                  => $d['os'],
                'device_type'         => $d['device_type'],
                'language'            => substr((string) $request->getPreferredLanguage(), 0, 12) ?: null,
                'user_agent'          => $request->userAgent(),
            ]);

            // Run the rule engine (creates risk alerts), then settle score+result.
            $score = $engine->evaluate($verification, $unit, $policy);

            // Categories the engine flagged for THIS scan — drives the public
            // block reason + message (locked / country / city / device / hit …).
            $cats = \App\Models\RiskAlert::where('verification_log_id', $verification->id)->pluck('category')->all();

            $block = match (true) {
                (bool) $recall                         => 'recalled',
                in_array('ip_blocked', $cats, true)    => 'ip',
                in_array('locked_scope', $cats, true)  => 'locked',
                in_array('vpn_blocked', $cats, true)   => 'vpn',
                $expired                               => 'expired',
                $blocked                               => 'invalid',
                in_array('unauthorized_market', $cats, true) => 'country',
                in_array('unauthorized_city', $cats, true)   => 'city',
                in_array('device_limit_exceeded', $cats, true) => 'device',
                in_array('scan_limit_exceeded', $cats, true)   => 'hit',
                default                                => null,
            };

            $result = match ($block) {
                'recalled' => 'recalled',
                'locked'   => 'locked',
                'expired'  => 'expired',
                'invalid'  => 'invalid',
                'ip', 'vpn', 'rate', 'country', 'city', 'device', 'hit' => 'blocked',
                default    => ($score >= 41 ? 'suspicious' : 'genuine'),
            };
            $verification->update(['risk_score' => $score, 'result' => $result]);

            // ── Scan-intelligence rules (8 configurable scenarios) ──
            $intel          = app(ScanIntelligence::class)->evaluate($verification, $unit, $cfg);
            $alerts         = $intel['alerts'];
            $certificate    = $intel['certificate'];
            $intendedRegion = $intel['intendedRegion'];

            // Apply a hard block from the rules, but never downgrade a stronger
            // integrity block (recall / locked / expired …) already in place.
            if (!$block && $intel['block']) {
                $block  = $intel['block'];
                $result = $block === 'locked' ? 'locked' : 'blocked';
                $verification->update(['result' => $result]);
            }

            // Permanently lock the UUC when a rule demands it (rule 4).
            if ($intel['lock']) {
                $this->autoLockUnit($unit, $intel['lock']['reason']);
            }

            // ── Post-lock / over-limit forensics ──
            // Count scans that were blocked by access-control enforcement AFTER a
            // unit was locked or its scan/device/geo/IP/VPN/rate limit was crossed,
            // so abuse on a flagged UUC can be identified later.
            if (in_array($block, ['locked', 'hit', 'device', 'country', 'city', 'ip', 'vpn', 'rate'], true)) {
                $unit->increment('blocked_scan_count');
                $unit->forceFill(['last_blocked_scan_at' => now()])->save();
            }

            // ── VPN / proxy usage tracking ──
            // Record every scan that came over a VPN/proxy network (whether or not
            // it was blocked), so VPN abuse on a UUC is visible historically.
            if (!empty($g['is_proxy'])) {
                $unit->increment('vpn_scan_count');
                $unit->forceFill(['last_vpn_scan_at' => now()])->save();
            }

            // Aggregate scan stats for the "Verification Information" panel.
            $agg = VerificationLog::where('uuc_code', $code);
            $scanStats = [
                'count'     => (clone $agg)->count(),
                'first'     => (clone $agg)->min('created_at'),
                'last'      => (clone $agg)->max('created_at'),
                'countries' => (clone $agg)->whereNotNull('country_code')->distinct()->count('country_code'),
            ];
        }

        // Unit lifecycle trace (most recent first) — drives the Tracking timeline.
        // History count is admin-configurable (0 = all, capped for safety).
        $historyLimit = (int) ($cfg['history_limit'] ?? 10);
        $logs = $unit
            ? BatchUnitLog::where('batch_unit_id', $unit->id)->latest()
                ->limit($historyLimit > 0 ? $historyLimit : 500)->get()
            : collect();

        // Per-scan verification history (carries geo + device per scan).
        $verifications = $unit
            ? VerificationLog::where('uuc_code', $code)->latest()
                ->limit($historyLimit > 0 ? $historyLimit : 500)->get()
            : collect();

        $view = match ($cfg['verify_style'] ?? 'style1') {
            'style2' => 'verify.style2',
            'style3' => 'verify.style3',
            'style4' => 'verify.style4',
            'style5' => 'verify.style5',
            default  => 'verify.verify',
        };

        return view($view, compact('unit', 'code', 'logs', 'verifications', 'verification', 'recall', 'scanStats', 'policy', 'block', 'cfg', 'alerts', 'certificate', 'intendedRegion'));
    }

    /**
     * Permanently lock a UUC from the verification flow (scan-intelligence rule 4):
     * create/keep a code-scope locked policy and record the lock reason on the unit.
     * Mirrors AntiCounterfeitController::quickLockUnit so Access Control shows it.
     */
    private function autoLockUnit(BatchUnit $unit, string $reason): void
    {
        $existing = VerificationPolicy::where('scope_type', 'codes')->get()
            ->first(fn ($p) => (array) $p->uuc_codes === [$unit->secret_code]);

        if ($existing) {
            if (!$existing->locked) {
                $existing->update(['locked' => true, 'active' => true, 'notes' => $reason]);
            }
        } else {
            VerificationPolicy::create([
                'name'       => 'Auto lock — ' . $unit->secret_code,
                'scope_type' => 'codes',
                'uuc_codes'  => [$unit->secret_code],
                'locked'     => true,
                'active'     => true,
                'notes'      => $reason,
            ]);
        }

        if (!$unit->locked_at) {
            $unit->forceFill(['lock_reason' => $reason, 'locked_at' => now()])->save();
        }
    }

    /** Public: submit a counterfeit / problem report from the verification page. */
    public function report(string $code, Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'reporter_name'  => 'required|string|max:120',
            'reporter_phone' => 'required|string|max:40',
            'reporter_email' => 'nullable|email|max:160',
            'message'        => 'nullable|string|max:2000',
            'reason'         => 'nullable|string|max:40',
        ]);

        $unit = BatchUnit::with('batch')->where('secret_code', $code)->first();
        $log  = VerificationLog::where('uuc_code', $code)->latest()->first();

        CounterfeitReport::create([
            'uuc_code'            => $code,
            'verification_log_id' => $log?->id,
            'product_id'          => $unit?->batch?->product_id,
            'batch_id'            => $unit?->batch_id,
            'reason'              => $data['reason'] ?? null,
            'reporter_name'       => $data['reporter_name'],
            'reporter_phone'      => $data['reporter_phone'],
            'reporter_email'      => $data['reporter_email'] ?? null,
            'message'             => $data['message'] ?? null,
            'city'                => $log?->city,
            'country'             => $log?->country,
            'ip_address'          => $request->ip(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Report submitted. Thank you.']);
        }
        return back()->with('success', 'Report submitted. Thank you.');
    }

    /**
     * Public: a patient/partner requests the product's details by email.
     * The email is only sent when the product is confirmed GENUINE.
     */
    public function requestInfo(string $code, Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'email'         => 'required|email|max:160',
            'phone'         => 'nullable|string|max:40',
            'country'       => 'nullable|string|max:80',
            'city'          => 'nullable|string|max:80',
            'address'       => 'nullable|string|max:255',
            'partner_name'  => 'nullable|string|max:120',
            'partner_phone' => 'nullable|string|max:40',
            'whatsapp'      => 'nullable|string|max:40',
        ]);

        $unit = BatchUnit::with('batch.product')->where('secret_code', $code)->first();

        // Gate: only genuine / original products qualify for an info email.
        $genuine = $unit
            && !ProductRecall::activeForUnit($unit)
            && !(VerificationPolicy::resolveForUnit($unit)?->locked)
            && !($unit->batch?->expiry_date && $unit->batch->expiry_date->isPast())
            && !in_array($unit->status, ['blocked', 'inactive', 'expired'], true);

        if (!$genuine) {
            return response()->json([
                'success' => false,
                'message' => 'Product information can only be emailed for genuine products.',
            ], 422);
        }

        $req = ProductInfoRequest::create([
            'uuc_code'      => $code,
            'product_id'    => $unit->batch?->product_id,
            'batch_id'      => $unit->batch_id,
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'country'       => $data['country'] ?? null,
            'city'          => $data['city'] ?? null,
            'address'       => $data['address'] ?? null,
            'partner_name'  => $data['partner_name'] ?? null,
            'partner_phone' => $data['partner_phone'] ?? null,
            'whatsapp'      => $data['whatsapp'] ?? null,
        ]);

        try {
            Mail::to($data['email'])->send(new ProductInfoMail($unit, $data['name']));
            $req->update(['emailed' => true]);
        } catch (\Throwable $e) {
            // Don't fail the request if the mailer is unavailable — record stays.
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Product details have been sent to your email.',
        ]);
    }

    /**
     * Public: returning-scan genuine certificate (scan-intelligence rule 8).
     * The visitor fills a short info form and downloads a PDF confirming the
     * product is genuine, with product / batch / origin details.
     */
    public function certificate(string $code, Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:120',
            'phone'   => 'nullable|string|max:40',
            'email'   => 'nullable|email|max:160',
            'country' => 'nullable|string|max:80',
            'city'    => 'nullable|string|max:80',
        ]);

        $unit = BatchUnit::with('batch.product')->where('secret_code', $code)->first();

        // Only issue a certificate for a genuine, non-blocked product.
        $genuine = $unit
            && !ProductRecall::activeForUnit($unit)
            && !(VerificationPolicy::resolveForUnit($unit)?->locked)
            && !($unit->batch?->expiry_date && $unit->batch->expiry_date->isPast())
            && !in_array($unit->status, ['blocked', 'inactive', 'expired'], true);

        if (!$genuine) {
            return back()->with('error', 'A certificate can only be issued for a genuine product.');
        }

        $cfg = Setting::config();
        $log = VerificationLog::where('uuc_code', $code)->latest()->first();

        $pdf = Pdf::loadView('verify.certificate', [
            'unit'    => $unit,
            'product' => $unit->batch?->product,
            'batch'   => $unit->batch,
            'code'    => $code,
            'cfg'     => $cfg,
            'log'     => $log,
            'visitor' => $data,
            'issued'  => now(),
        ])->setPaper('a4');

        return $pdf->download('genuine-certificate-' . $code . '.pdf');
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

        // Only the original batch units (partial_batch_ref = NULL) are reconciled
        // here. Units added later via Partial Batch extensions are left untouched.
        // Remove from the end (serials beyond target) — generated-only
        $removed = $batch->units()
            ->whereNull('partial_batch_ref')
            ->where('serial_number', '>', $target)
            ->where('status', 'generated')
            ->delete();

        // Anything still beyond target is locked (printed/packed/etc.)
        $blocked = $batch->units()
            ->whereNull('partial_batch_ref')
            ->where('serial_number', '>', $target)
            ->count();

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

        $existing = $batch->units()->whereNull('partial_batch_ref')
                          ->where('serial_number', '<=', $target)
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
