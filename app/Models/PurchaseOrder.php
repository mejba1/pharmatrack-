<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'po_number', 'buyer_id', 'created_by', 'po_date', 'required_by_date',
        'currency', 'payment_terms', 'incoterms', 'port_of_loading', 'port_of_discharge',
        'subtotal', 'freight', 'total_value', 'status',
        'acknowledged_date', 'acknowledged_by', 'remarks',
    ];

    protected $casts = [
        'po_date'           => 'date',
        'required_by_date'  => 'date',
        'acknowledged_date' => 'date',
        'subtotal'          => 'decimal:2',
        'freight'           => 'decimal:2',
        'total_value'       => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────
    public function buyer()    { return $this->belongsTo(Customer::class, 'buyer_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function acknowledger() { return $this->belongsTo(User::class, 'acknowledged_by'); }
    public function lines()    { return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_number'); }
    public function salesOrder() { return $this->hasOne(SalesOrder::class); }
    public function documents() { return $this->morphMany(OrderDocument::class, 'documentable')->where('is_active', true)->latest(); }

    /**
     * Progress through the document chain PO → SO → PI → CI. Uses already-loaded
     * relations when available (eager-load salesOrder.proformaInvoice.commercialInvoices).
     *
     * @return array{po:bool,so:bool,pi:bool,ci:bool,ci_count:int,so_status:?string,pi_status:?string,stage:string,step:int}
     */
    public function chainStages(): array
    {
        $so = $this->salesOrder;
        $pi = $so?->proformaInvoice;
        $ciCount = $pi ? $pi->commercialInvoices->count() : 0;

        return [
            'po'        => true,
            'so'        => (bool) $so,
            'pi'        => (bool) $pi,
            'ci'        => $ciCount > 0,
            'ci_count'  => $ciCount,
            'so_id'     => $so?->id,
            'pi_id'     => $pi?->id,
            'so_status' => $so?->status,
            'pi_status' => $pi?->status,
            'stage'     => $ciCount > 0 ? 'CI' : ($pi ? 'PI' : ($so ? 'SO' : 'PO')),
            'step'      => $ciCount > 0 ? 4 : ($pi ? 3 : ($so ? 2 : 1)),
        ];
    }

    // ── Accessors ──────────────────────────────────────────────────────────
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'acknowledged' => 'approved',
            'sent'         => 'shipped',
            'cancelled'    => 'cancelled',
            default        => 'draft',   // draft
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    /** Next per-year PO number, e.g. PO-2026-0001. */
    public static function nextNumber(): string
    {
        $prefix = 'PO-' . now()->format('Y') . '-';
        $last = static::withTrashed()->where('po_number', 'like', $prefix . '%')
            ->orderByDesc('po_number')->value('po_number');
        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
