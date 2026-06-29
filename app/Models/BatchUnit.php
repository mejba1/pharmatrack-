<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchUnit extends Model
{
    protected $fillable = [
        'batch_id',
        'partial_batch_ref',
        'serial_number',
        'secret_code',
        'unique_number',
        'status',
        'lock_reason',
        'locked_at',
        'blocked_scan_count',
        'last_blocked_scan_at',
        'vpn_scan_count',
        'last_vpn_scan_at',
        'customer_sale_id',
        'sales_order_id',
        'sold_to_id',
        'sold_at',
    ];

    protected $casts = [
        'serial_number'        => 'integer',
        'blocked_scan_count'   => 'integer',
        'vpn_scan_count'       => 'integer',
        'locked_at'            => 'datetime',
        'last_blocked_scan_at' => 'datetime',
        'last_vpn_scan_at'     => 'datetime',
        'sold_at'              => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function sale()
    {
        return $this->belongsTo(CustomerSale::class, 'customer_sale_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function soldTo()
    {
        return $this->belongsTo(Customer::class, 'sold_to_id');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active', 'verified'   => 'badge-approved',
            'blocked', 'expired'   => 'badge-cancelled',
            'inactive'             => 'badge-cancelled',
            default                => 'badge-pending',  // generated, printing, packed, scanned
        };
    }
}
