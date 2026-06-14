<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchExtension extends Model
{
    protected $fillable = [
        'batch_id',
        'product_id',
        'partial_ref',
        'additional_quantity',
        'serial_mode',
        'serial_start',
        'serial_end',
        'manufacture_date',
        'expiry_date',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'additional_quantity' => 'integer',
        'serial_start'        => 'integer',
        'serial_end'          => 'integer',
        'manufacture_date'    => 'date',
        'expiry_date'         => 'date',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getSerialModeLabelAttribute(): string
    {
        return $this->serial_mode === 'restart'
            ? 'Restarted from 1'
            : 'Continued from previous';
    }
}
