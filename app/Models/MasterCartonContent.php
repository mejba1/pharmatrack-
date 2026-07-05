<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCartonContent extends Model
{
    protected $fillable = [
        'master_carton_id', 'product_id', 'batch_id', 'partial_batch_ref',
        'serial_start', 'serial_end', 'quantity',
    ];

    protected $casts = [
        'serial_start' => 'integer',
        'serial_end'   => 'integer',
        'quantity'     => 'integer',
    ];

    public function carton()
    {
        return $this->belongsTo(MasterCarton::class, 'master_carton_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function getSerialRangeAttribute(): string
    {
        return "{$this->serial_start}–{$this->serial_end}";
    }
}
