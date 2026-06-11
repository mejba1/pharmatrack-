<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchUnitLog extends Model
{
    protected $fillable = [
        'batch_id',
        'batch_unit_id',
        'event',
        'from_status',
        'to_status',
        'quantity',
        'note',
        'performed_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function unit()
    {
        return $this->belongsTo(BatchUnit::class, 'batch_unit_id');
    }
}
