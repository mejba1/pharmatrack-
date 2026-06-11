<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchUnit extends Model
{
    protected $fillable = [
        'batch_id',
        'serial_number',
        'secret_code',
        'unique_number',
        'status',
    ];

    protected $casts = [
        'serial_number' => 'integer',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
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
