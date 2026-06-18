<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationLog extends Model
{
    protected $fillable = [
        'verification_number', 'uuc_code',
        'batch_unit_id', 'batch_id', 'product_id',
        'result', 'risk_score',
        'ip_address', 'country', 'country_code', 'city', 'region', 'isp',
        'latitude', 'longitude', 'gps_accuracy', 'is_proxy',
        'browser', 'os', 'device_type', 'language', 'timezone', 'user_agent',
    ];

    protected $casts = [
        'is_proxy'   => 'boolean',
        'risk_score' => 'integer',
        'latitude'   => 'decimal:7',
        'longitude'  => 'decimal:7',
    ];

    public function batch()    { return $this->belongsTo(Batch::class); }
    public function product()  { return $this->belongsTo(Product::class); }
    public function unit()     { return $this->belongsTo(BatchUnit::class, 'batch_unit_id'); }
    public function alerts()   { return $this->hasMany(RiskAlert::class); }

    /** Badge class for the verification result. */
    public function getResultBadgeClassAttribute(): string
    {
        return match ($this->result) {
            'genuine'    => 'badge-approved',
            'suspicious' => 'badge-pending',
            default      => 'badge-cancelled',   // invalid | expired | recalled
        };
    }

    /** Next sequential verification number for today. */
    public static function nextNumber(): string
    {
        $prefix = 'VER-' . now()->format('Ymd') . '-';
        $last   = static::where('verification_number', 'like', $prefix . '%')
                        ->orderByDesc('verification_number')
                        ->value('verification_number');
        $seq = $last ? ((int) substr($last, -6) + 1) : 1;
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
