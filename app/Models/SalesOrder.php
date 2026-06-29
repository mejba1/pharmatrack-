<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'so_number', 'purchase_order_id', 'created_by', 'customer_id', 'ship_to_country_id',
        'so_date', 'estimated_delivery_date', 'currency', 'payment_terms', 'incoterms',
        'port_of_loading', 'port_of_discharge', 'subtotal', 'freight', 'total_value', 'status', 'remarks',
    ];

    protected $casts = [
        'so_date'                 => 'date',
        'estimated_delivery_date' => 'date',
        'subtotal'                => 'decimal:2',
        'freight'                 => 'decimal:2',
        'total_value'             => 'decimal:2',
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function customer()      { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function creator()       { return $this->belongsTo(User::class, 'created_by'); }
    public function lines()         { return $this->hasMany(SalesOrderLine::class)->orderBy('line_number'); }
    public function units()         { return $this->hasMany(BatchUnit::class, 'sales_order_id'); }
    public function documents()     { return $this->morphMany(OrderDocument::class, 'documentable')->where('is_active', true)->latest(); }
    public function proformaInvoice() { return $this->hasOne(ProformaInvoice::class); }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'confirmed'  => 'approved',
            'pi_issued'  => 'shipped',
            'completed'  => 'approved',
            'cancelled'  => 'cancelled',
            default      => 'draft',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'pi_issued' ? 'PI Issued' : ucfirst($this->status);
    }

    /** Next per-year SO number, e.g. SO-2026-0001. */
    public static function nextNumber(): string
    {
        $prefix = 'SO-' . now()->format('Y') . '-';
        $last = static::withTrashed()->where('so_number', 'like', $prefix . '%')
            ->orderByDesc('so_number')->value('so_number');
        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
