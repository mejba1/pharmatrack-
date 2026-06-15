<?php

namespace App\Http\Controllers;

use App\Models\Consignment;
use App\Models\ConsignmentScan;
use App\Models\MasterCarton;
use App\Models\MasterCartonScan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Shipment / Consignment management — groups Master Cartons into one parent
 * logistics unit with its own QR, a dispatch/receive workflow, and a
 * single-scan verification page.
 */
class ConsignmentController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'destination', 'search', 'date_from', 'date_to']);

        $consignments = Consignment::with(['cartons'])
                        ->filter($filters)
                        ->orderByDesc('id')
                        ->paginate(15)
                        ->withQueryString();

        $stats = [
            'total'      => Consignment::count(),
            'created'    => Consignment::where('status', 'created')->count(),
            'in_transit' => Consignment::whereIn('status', ['dispatched', 'in_transit'])->count(),
            'received'   => Consignment::where('status', 'received')->count(),
            'cartons'    => MasterCarton::whereNotNull('consignment_id')->count(),
        ];

        return view('shipments', compact('consignments', 'stats', 'filters'));
    }

    // ── Create ────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'destination'  => 'nullable|string|max:255',
            'origin'       => 'nullable|string|max:255',
            'carrier'      => 'nullable|string|max:255',
            'vehicle_no'   => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:2000',
            'carton_ids'   => 'nullable|array',
            'carton_ids.*' => 'integer|exists:master_cartons,id',
        ]);

        $consignment = DB::transaction(function () use ($data) {
            $consignment = Consignment::create([
                'consignment_number' => Consignment::generateNumber(),
                'qr_code'            => Consignment::generateQr(),
                'origin'             => $data['origin'] ?? 'Factory',
                'destination'        => $data['destination'] ?? null,
                'carrier'            => $data['carrier'] ?? null,
                'vehicle_no'         => $data['vehicle_no'] ?? null,
                'status'             => 'created',
                'notes'              => $data['notes'] ?? null,
            ]);

            $this->assignCartons($consignment, $data['carton_ids'] ?? []);

            ConsignmentScan::create([
                'consignment_id' => $consignment->id,
                'event'          => 'created',
                'performed_by'   => optional(auth()->user())->name ?? 'system',
                'location'       => $consignment->origin,
                'note'           => 'Shipment created.',
            ]);

            return $consignment;
        });

        $message = "Shipment {$consignment->consignment_number} created with {$consignment->carton_count} carton(s).";
        if ($request->wantsJson()) {
            session()->flash('success', $message);
            return response()->json(['success' => true, 'message' => $message, 'redirect' => route('shipments')]);
        }
        return redirect()->route('shipments')->with('success', $message);
    }

    /** Assign packed, unassigned cartons to a consignment (skips ineligible ones). */
    private function assignCartons(Consignment $consignment, array $cartonIds): int
    {
        if (empty($cartonIds)) {
            return 0;
        }
        return MasterCarton::whereIn('id', $cartonIds)
            ->whereNull('consignment_id')
            ->where('packed_quantity', '>', 0)
            ->whereNotIn('status', ['dispatched', 'received'])
            ->update(['consignment_id' => $consignment->id]);
    }

    // ── Manage cartons in a consignment ───────────────────────────────────

    public function addCartons(Request $request, Consignment $consignment): JsonResponse
    {
        $data = $request->validate([
            'carton_ids'   => 'required|array|min:1',
            'carton_ids.*' => 'integer|exists:master_cartons,id',
        ]);

        if (in_array($consignment->status, ['dispatched', 'in_transit', 'received', 'closed'], true)) {
            return response()->json(['success' => false, 'errors' => ['shipment' => ["Shipment {$consignment->consignment_number} has already left the factory; its contents are locked."]]], 422);
        }

        $added = $this->assignCartons($consignment, $data['carton_ids']);

        return response()->json([
            'success'  => true,
            'message'  => "{$added} carton(s) added to {$consignment->consignment_number}.",
            'shipment' => $this->payload($consignment->fresh('cartons'), true),
        ]);
    }

    public function removeCarton(Consignment $consignment, MasterCarton $masterCarton): JsonResponse
    {
        if (in_array($consignment->status, ['dispatched', 'in_transit', 'received', 'closed'], true)) {
            return response()->json(['success' => false, 'errors' => ['shipment' => ['Shipment is locked; cartons can no longer be removed.']]], 422);
        }
        if ((int) $masterCarton->consignment_id === (int) $consignment->id) {
            $masterCarton->update(['consignment_id' => null]);
        }

        return response()->json([
            'success'  => true,
            'message'  => "Carton {$masterCarton->carton_number} removed from shipment.",
            'shipment' => $this->payload($consignment->fresh('cartons'), true),
        ]);
    }

    // ── JSON: packed cartons available to add (not yet in a consignment) ───

    public function availableCartons(): JsonResponse
    {
        $cartons = MasterCarton::with(['product', 'batch'])
            ->whereNull('consignment_id')
            ->where('packed_quantity', '>', 0)
            ->whereNotIn('status', ['dispatched', 'received'])
            ->orderBy('carton_number')
            ->get();

        return response()->json($cartons->map(fn ($c) => [
            'id'            => $c->id,
            'carton_number' => $c->carton_number,
            'product'       => $c->products_summary,
            'batch'         => $c->batches_summary,
            'packed'        => $c->packed_quantity,
            'label'         => $c->carton_number . ' · ' . $c->products_summary . ' · ' . $c->packed_quantity . ' units',
        ]));
    }

    // ── JSON: single consignment (view modal) ─────────────────────────────

    public function show(Consignment $consignment): JsonResponse
    {
        return response()->json($this->payload($consignment, true));
    }

    // ── Movement: dispatch / in-transit / receive (cascades to cartons) ───

    public function move(Request $request, Consignment $consignment): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'event'    => 'required|in:dispatched,in_transit,received',
            'location' => 'nullable|string|max:255',
            'note'     => 'nullable|string|max:1000',
        ]);

        if ($consignment->carton_count === 0) {
            return $this->fail($request, 'event', "Shipment {$consignment->consignment_number} has no cartons yet.");
        }
        if ($data['event'] === 'received' && !$consignment->dispatched_at) {
            return $this->fail($request, 'event', "Shipment {$consignment->consignment_number} must be dispatched before it can be received.");
        }

        DB::transaction(function () use ($consignment, $data) {
            $event = $data['event'];

            $consignment->update([
                'status'        => $event,
                'dispatched_at' => $event === 'dispatched' ? now() : $consignment->dispatched_at,
                'received_at'   => $event === 'received' ? now() : $consignment->received_at,
            ]);

            // Cascade to the cartons that ride in this consignment.
            $cartonStatus = $event === 'received' ? 'received' : ($event === 'dispatched' ? 'dispatched' : null);
            if ($cartonStatus) {
                foreach ($consignment->cartons()->get() as $carton) {
                    $carton->update([
                        'status'        => $cartonStatus,
                        'dispatched_at' => $cartonStatus === 'dispatched' ? now() : $carton->dispatched_at,
                        'received_at'   => $cartonStatus === 'received' ? now() : $carton->received_at,
                    ]);
                    MasterCartonScan::create([
                        'master_carton_id' => $carton->id,
                        'event'            => $cartonStatus,
                        'performed_by'     => optional(auth()->user())->name ?? 'system',
                        'location'         => $data['location'] ?? null,
                        'note'             => "Via shipment {$consignment->consignment_number}.",
                    ]);
                }
            }

            ConsignmentScan::create([
                'consignment_id' => $consignment->id,
                'event'          => $event,
                'performed_by'   => optional(auth()->user())->name ?? 'system',
                'location'       => $data['location'] ?? null,
                'note'           => $data['note'] ?? null,
            ]);
        });

        $label   = ['dispatched' => 'dispatched from factory', 'in_transit' => 'marked in transit', 'received' => 'received'][$data['event']];
        $message = "Shipment {$consignment->consignment_number} {$label}.";
        if ($request->wantsJson()) {
            session()->flash('success', $message);
            return response()->json(['success' => true, 'message' => $message, 'redirect' => route('shipments')]);
        }
        return redirect()->route('shipments')->with('success', $message);
    }

    // ── Receiving verification: per-carton received / damaged / missing ───

    public function receiveCarton(Request $request, Consignment $consignment, MasterCarton $masterCarton): JsonResponse
    {
        $data = $request->validate([
            'outcome'  => 'required|in:received,damaged,missing',
            'location' => 'nullable|string|max:255',
            'note'     => 'nullable|string|max:1000',
            'evidence' => 'nullable|image|max:4096',
        ]);

        if ((int) $masterCarton->consignment_id !== (int) $consignment->id) {
            return response()->json(['success' => false, 'errors' => ['carton' => ['Carton is not part of this shipment.']]], 422);
        }
        if (!$consignment->dispatched_at) {
            return response()->json(['success' => false, 'errors' => ['carton' => ['Dispatch the shipment before receiving cartons.']]], 422);
        }

        $path = $masterCarton->evidence_path;
        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('carton-evidence', 'public');
        }

        $attrs = match ($data['outcome']) {
            'received' => ['status' => 'received', 'carton_condition' => 'good',    'received_at' => now()],
            'damaged'  => ['status' => 'received', 'carton_condition' => 'damaged', 'received_at' => now()],
            'missing'  => ['carton_condition' => 'missing', 'received_at' => null],
        };

        $masterCarton->update($attrs + [
            'condition_note'    => $data['note'] ?? $masterCarton->condition_note,
            'evidence_path'     => $path,
            'received_location' => $data['location'] ?? $consignment->destination,
        ]);

        MasterCartonScan::create([
            'master_carton_id' => $masterCarton->id,
            'event'            => $data['outcome'] === 'missing' ? 'missing' : ($data['outcome'] === 'damaged' ? 'damaged' : 'received'),
            'performed_by'     => optional(auth()->user())->name ?? 'system',
            'location'         => $data['location'] ?? $consignment->destination,
            'note'             => $data['note'] ?? null,
        ]);

        // Auto-mark the shipment received once no cartons are still pending
        // (everything has been received, damaged, or recorded missing).
        $consignment->refresh()->loadMissing('cartons');
        if ($consignment->pending_cartons->isEmpty()) {
            $consignment->update(['status' => 'received', 'received_at' => $consignment->received_at ?? now()]);
        }

        return response()->json([
            'success'  => true,
            'message'  => "Carton {$masterCarton->carton_number} marked {$data['outcome']}.",
            'shipment' => $this->payload($consignment->fresh('cartons'), true),
        ]);
    }

    // ── Printable shipment QR labels & PDF (single / multiple / date-wise) ─

    /** Browser print view; honours list filters + an explicit id selection. */
    public function labels(Request $request): View
    {
        $filters      = $request->only(['status', 'destination', 'search', 'date_from', 'date_to', 'ids']);
        $consignments = $this->labelsQuery($filters)->paginate(60)->withQueryString();
        $title        = $this->labelsTitle($filters);

        return view('shipment-labels', compact('consignments', 'title', 'filters'));
    }

    /** Downloadable PDF of shipment QR labels; same filters as the print view. */
    public function labelsPdf(Request $request)
    {
        $filters      = $request->only(['status', 'destination', 'search', 'date_from', 'date_to', 'ids']);
        $consignments = $this->labelsQuery($filters)->get();
        abort_if($consignments->isEmpty(), 404, 'No shipments match the selected filters.');

        $title = $this->labelsTitle($filters);
        $name  = \Illuminate\Support\Str::slug($title ?: 'shipment', '_') . '_qr_labels';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.shipment-labels', compact('consignments', 'title'))
                    ->setPaper('a4', 'portrait')
                    ->setOption('isRemoteEnabled', true);
        return $pdf->download("{$name}.pdf");
    }

    private function labelsQuery(array $filters)
    {
        return Consignment::with('cartons')->filter($filters)->orderBy('consignment_number');
    }

    private function labelsTitle(array $filters): string
    {
        if (!empty($filters['ids'])) {
            return 'Selected Shipments';
        }
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? '…';
            $to   = $filters['date_to'] ?? '…';
            return "Shipments {$from} → {$to}";
        }
        if (!empty($filters['destination'])) {
            return 'Shipments to ' . $filters['destination'];
        }
        return 'All Shipments';
    }

    // ── Public parent-QR scan page ────────────────────────────────────────

    public function scan(Request $request, string $qr): View
    {
        $consignment = Consignment::with(['cartons.contents.product', 'cartons.contents.batch', 'cartons.product', 'cartons.batch'])
                        ->where('qr_code', $qr)->first();

        if ($consignment) {
            ConsignmentScan::create([
                'consignment_id' => $consignment->id,
                'event'          => 'scanned',
                'performed_by'   => 'public',
                'ip_address'     => $request->ip(),
                'note'           => 'Parent QR scan.',
            ]);
        }

        return view('shipment-verify', compact('consignment', 'qr'));
    }

    // ── Destroy ───────────────────────────────────────────────────────────

    public function destroy(Consignment $consignment): RedirectResponse
    {
        $num = $consignment->consignment_number;
        DB::transaction(function () use ($consignment) {
            $consignment->cartons()->update(['consignment_id' => null]); // release cartons
            $consignment->delete();
        });

        return redirect()->route('shipments')->with('success', "Shipment {$num} removed; its cartons were released.");
    }

    // ── Payload helper ────────────────────────────────────────────────────

    private function payload(Consignment $consignment, bool $full = false): array
    {
        $consignment->loadMissing(['cartons.contents.product', 'cartons.contents.batch', 'cartons.product', 'cartons.batch']);

        $base = [
            'id'              => $consignment->id,
            'shipment_number' => $consignment->consignment_number,
            'qr_code'         => $consignment->qr_code,
            'origin'          => $consignment->origin,
            'destination'     => $consignment->destination,
            'carrier'         => $consignment->carrier,
            'vehicle_no'      => $consignment->vehicle_no,
            'status'          => $consignment->status,
            'status_label'    => $consignment->status_label,
            'status_badge'    => $consignment->status_badge_class,
            'carton_count'    => $consignment->carton_count,
            'total_units'     => $consignment->total_units,
            'product_list'    => $consignment->product_list,
            'batch_list'      => $consignment->batch_list,
            'received_count'  => $consignment->received_carton_count,
            'received_ok'     => $consignment->received_ok_count,
            'pending_cartons' => $consignment->pending_cartons,
            'missing_cartons' => $consignment->missing_cartons,
            'dispatched_at'   => $consignment->dispatched_at?->toIso8601String(),
            'received_at'     => $consignment->received_at?->toIso8601String(),
            'scan_url'        => route('shipment.scan', $consignment->qr_code),
        ];

        if (!$full) {
            return $base;
        }

        $base['damaged_cartons'] = $consignment->damaged_cartons;
        $base['cartons'] = $consignment->cartons->map(fn ($c) => [
            'id'              => $c->id,
            'carton_number'   => $c->carton_number,
            'product'         => $c->products_summary,
            'batch'           => $c->batches_summary,
            'packed'          => $c->packed_quantity,
            'capacity'        => $c->capacity,
            'status'          => $c->status,
            'status_label'    => $c->status_label,
            'status_badge'    => $c->status_badge_class,
            'condition'       => $c->carton_condition,
            'condition_label' => $c->condition_label,
            'condition_badge' => $c->condition_badge_class,
            'received'        => (bool) $c->received_at,
            'evidence_url'    => $c->evidence_url,
            'condition_note'  => $c->condition_note,
        ])->values();

        $base['scans'] = $consignment->scans->map(fn ($s) => [
            'event' => $s->event, 'event_label' => $s->event_label, 'event_icon' => $s->event_icon,
            'performed_by' => $s->performed_by, 'location' => $s->location,
            'at' => $s->created_at?->toIso8601String(),
        ])->values();

        return $base;
    }

    private function fail(Request $request, string $field, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'errors' => [$field => [$message]]], 422);
        }
        return back()->withInput()->withErrors([$field => $message]);
    }
}
