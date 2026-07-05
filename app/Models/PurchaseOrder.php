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
        'promo_code_id', 'promo_code', 'discount_scope', 'discount_type', 'discount_value', 'discount_product_id',
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
    public function promoCode() { return $this->belongsTo(PromoCode::class); }
    public function discountProduct() { return $this->belongsTo(Product::class, 'discount_product_id'); }

    /** Human-readable promo summary, or null when no code was applied. */
    public function getPromoLabelAttribute(): ?string
    {
        if (! $this->promo_code) return null;
        if ($this->discount_scope === 'none') return "{$this->promo_code} · no discount";

        $val = $this->discount_type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->discount_value, 2), '0'), '.') . '%'
            : number_format((float) $this->discount_value, 2);
        $where = $this->discount_scope === 'product'
            ? ($this->discountProduct?->name ?? 'a product')
            : 'order total';

        return "{$this->promo_code} · {$val} off {$where}";
    }

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

    /**
     * May the buying customer still change this order from the portal?
     * Only while it's an unprocessed request: still "sent"/"draft" and not yet
     * converted to a Sales Order. Once staff acknowledge it or push it down the
     * chain (SO/PI/CI) it becomes read-only for the customer.
     *
     * Uses the already-loaded salesOrder relation when present to avoid a query.
     */
    public function isEditableByCustomer(): bool
    {
        if (! in_array($this->status, ['sent', 'draft'], true)) {
            return false;
        }

        return $this->relationLoaded('salesOrder')
            ? ! $this->salesOrder
            : ! $this->salesOrder()->exists();
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
