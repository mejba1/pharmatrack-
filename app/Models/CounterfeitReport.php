<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterfeitReport extends Model
{
    protected $fillable = [
        'uuc_code', 'verification_log_id', 'product_id', 'batch_id', 'reason',
        'reporter_name', 'reporter_phone', 'reporter_email', 'message',
        'city', 'country', 'ip_address', 'status',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function batch()   { return $this->belongsTo(Batch::class); }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'resolved'  => 'badge-approved',
            'reviewing' => 'badge-pending',
            default     => 'badge-cancelled', // new
        };
    }
}
