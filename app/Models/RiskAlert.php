<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskAlert extends Model
{
    protected $fillable = [
        'alert_number', 'verification_log_id',
        'batch_unit_id', 'batch_id', 'product_id', 'uuc_code',
        'category', 'risk_level', 'risk_score',
        'country', 'latitude', 'longitude', 'description',
        'status', 'is_case', 'assigned_to', 'notes', 'resolved_at',
    ];

    protected $casts = [
        'is_case'     => 'boolean',
        'risk_score'  => 'integer',
        'latitude'    => 'decimal:7',
        'longitude'   => 'decimal:7',
        'resolved_at' => 'datetime',
    ];

    public function verificationLog() { return $this->belongsTo(VerificationLog::class); }
    public function batch()           { return $this->belongsTo(Batch::class); }
    public function product()         { return $this->belongsTo(Product::class); }

    /** Human label for the rule category. */
    public function getCategoryLabelAttribute(): string
    {
        return self::categoryLabels()[$this->category] ?? ucwords(str_replace('_', ' ', $this->category));
    }

    public function getRiskBadgeClassAttribute(): string
    {
        return match ($this->risk_level) {
            'critical' => 'badge-cancelled',
            'high'     => 'badge-cancelled',
            'medium'   => 'badge-pending',
            default    => 'badge-pending',   // low
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'resolved'      => 'badge-approved',
            'investigating' => 'badge-pending',
            'dismissed'     => 'badge-draft',
            default         => 'badge-cancelled', // open
        };
    }

    public static function categoryLabels(): array
    {
        return [
            'geographic_impossibility' => 'Geographic Impossibility',
            'excessive_scan_rate'      => 'Excessive Scan Rate',
            'unauthorized_market'      => 'Unauthorized Market',
            'batch_cluster'            => 'Batch Cluster',
            'expired_scan'             => 'Expired Product Scan',
            'recalled_scan'            => 'Recalled Batch Scan',
            'impossible_travel'        => 'Impossible Travel Speed',
            'multiple_device'          => 'Multiple Device Detection',
            'vpn_proxy'                => 'VPN / Proxy Detection',
            'blacklisted_location'     => 'Blacklisted Location',
            'scan_burst'               => 'Scan Burst',
            'sequential_uuc'           => 'Sequential UUC Abuse',
            'first_scan_mismatch'      => 'First Scan Country Mismatch',
            'destroyed_rejected'       => 'Destroyed / Rejected Product',
            'locked_scope'             => 'Locked (Policy)',
            'unauthorized_city'        => 'Unauthorized City',
            'scan_limit_exceeded'      => 'Scan / Hit Limit Exceeded',
            'device_limit_exceeded'    => 'Device Limit Exceeded',
        ];
    }

    public static function nextNumber(): string
    {
        $prefix = 'ALT-' . now()->format('Ymd') . '-';
        $last   = static::where('alert_number', 'like', $prefix . '%')
                        ->orderByDesc('alert_number')
                        ->value('alert_number');
        $seq = $last ? ((int) substr($last, -6) + 1) : 1;
        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
