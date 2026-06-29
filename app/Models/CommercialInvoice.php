<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ci_number', 'proforma_invoice_id', 'created_by', 'ci_date',
        'hs_code', 'country_of_origin', 'incoterms', 'port_of_loading', 'port_of_discharge',
        'currency', 'payment_terms', 'bank_name', 'bank_account_number', 'bank_swift_code',
        'subtotal', 'freight', 'insurance', 'total_value',
        'status', 'approved_by', 'approved_at', 'remarks',
    ];

    protected $casts = [
        'ci_date'     => 'date',
        'approved_at' => 'datetime',
        'subtotal'    => 'decimal:2',
        'freight'     => 'decimal:2',
        'insurance'   => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function proformaInvoice() { return $this->belongsTo(ProformaInvoice::class); }
    public function creator()         { return $this->belongsTo(User::class, 'created_by'); }
    public function approver()        { return $this->belongsTo(User::class, 'approved_by'); }
    public function lines()           { return $this->hasMany(CommercialInvoiceLine::class)->orderBy('line_number'); }
    public function documents()       { return $this->morphMany(OrderDocument::class, 'documentable')->where('is_active', true)->latest(); }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'approved', 'shipment_created' => 'approved',
            'pending_approval'             => 'shipped',
            'cancelled'                    => 'cancelled',
            default                        => 'draft',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /** Next per-year CI number, e.g. CI-2026-0001. */
    public static function nextNumber(): string
    {
        $prefix = 'CI-' . now()->format('Y') . '-';
        $last = static::withTrashed()->where('ci_number', 'like', $prefix . '%')
            ->orderByDesc('ci_number')->value('ci_number');
        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
