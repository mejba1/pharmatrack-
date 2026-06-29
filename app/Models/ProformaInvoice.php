<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProformaInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pi_number', 'sales_order_id', 'created_by', 'pi_date', 'valid_until',
        'currency', 'incoterms', 'port_of_loading', 'payment_terms',
        'bank_name', 'bank_account_number', 'bank_swift_code', 'bank_iban',
        'subtotal', 'tax_amount', 'freight', 'total_value',
        'status', 'approved_by', 'approved_at', 'rejection_reason', 'remarks',
    ];

    protected $casts = [
        'pi_date'     => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
        'subtotal'    => 'decimal:2',
        'tax_amount'  => 'decimal:2',
        'freight'     => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function approver()   { return $this->belongsTo(User::class, 'approved_by'); }
    public function lines()      { return $this->hasMany(ProformaInvoiceLine::class)->orderBy('line_number'); }
    public function commercialInvoices() { return $this->hasMany(CommercialInvoice::class); }
    public function customer()   { return $this->hasOneThrough(Customer::class, SalesOrder::class, 'id', 'id', 'sales_order_id', 'customer_id'); }
    public function documents()  { return $this->morphMany(OrderDocument::class, 'documentable')->where('is_active', true)->latest(); }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'approved'         => 'approved',
            'sent', 'pending_approval' => 'shipped',
            'rejected'         => 'cancelled',
            default            => 'draft',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /** Next per-year PI number, e.g. PI-2026-0001. */
    public static function nextNumber(): string
    {
        $prefix = 'PI-' . now()->format('Y') . '-';
        $last = static::withTrashed()->where('pi_number', 'like', $prefix . '%')
            ->orderByDesc('pi_number')->value('pi_number');
        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
