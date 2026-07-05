<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A customer-wise sale (one reference number) with many line items and the
 * specific UUC units assigned to the buyer for traceability.
 */
class CustomerSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number', 'customer_id', 'sale_date', 'status',
        'currency', 'subtotal', 'total', 'units_count', 'notes',
    ];

    protected $casts = [
        'sale_date'   => 'date',
        'subtotal'    => 'decimal:2',
        'total'       => 'decimal:2',
        'units_count' => 'integer',
    ];

    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function items()    { return $this->hasMany(CustomerSaleItem::class); }
    public function units()    { return $this->hasMany(BatchUnit::class, 'customer_sale_id'); }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'delivered', 'confirmed' => 'badge-approved',
            'cancelled'              => 'badge-cancelled',
            default                  => 'badge-pending',
        };
    }

    /** Next reference number, e.g. SAL-20260621-000001. */
    public static function nextReference(): string
    {
        $prefix = 'SAL-' . now()->format('Ymd') . '-';
        $last = static::withTrashed()->where('reference_number', 'like', $prefix . '%')
            ->orderByDesc('reference_number')->value('reference_number');
        $seq = $last ? ((int) substr($last, -6) + 1) : 1;
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
