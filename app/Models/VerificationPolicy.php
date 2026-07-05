<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationPolicy extends Model
{
    protected $fillable = [
        'name', 'scope_type', 'product_id', 'batch_id', 'uuc_codes',
        'locked', 'allow_all', 'allowed_countries', 'allowed_cities',
        'ip_whitelist', 'ip_blacklist', 'block_vpn', 'vpn_allowed_countries',
        'scan_limit', 'device_limit', 'active', 'notes',
    ];

    protected $casts = [
        'uuc_codes'             => 'array',
        'allowed_countries'     => 'array',
        'allowed_cities'        => 'array',
        'ip_whitelist'          => 'array',
        'ip_blacklist'          => 'array',
        'vpn_allowed_countries' => 'array',
        'locked'                => 'boolean',
        'allow_all'             => 'boolean',
        'block_vpn'             => 'boolean',
        'active'                => 'boolean',
        'scan_limit'            => 'integer',
        'device_limit'          => 'integer',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function batch()   { return $this->belongsTo(Batch::class); }

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope_type) {
            'product' => 'Product',
            'batch'   => 'Batch',
            'codes'   => 'Specific code(s)',
            'global'  => 'All UUC codes (global)',
            default   => ucfirst($this->scope_type),
        };
    }

    /**
     * The single global policy (baseline applied to every UUC code), if set.
     * Acts as the lowest-precedence default behind product/batch/code policies.
     */
    public static function global(): ?self
    {
        return static::where('scope_type', 'global')->first();
    }

    /**
     * Resolve the single most-specific active policy that applies to a unit.
     * Precedence: specific code(s) > batch > product > global (all codes).
     */
    public static function resolveForUnit(?BatchUnit $unit): ?self
    {
        if (!$unit) return null;

        $code = $unit->secret_code;
        $bid  = $unit->batch_id;
        $pid  = $unit->batch?->product_id;

        $matches = static::where('active', true)->get()->filter(function ($p) use ($code, $bid, $pid) {
            return match ($p->scope_type) {
                'codes'   => in_array($code, (array) $p->uuc_codes, true),
                'batch'   => (int) $p->batch_id === (int) $bid,
                'product' => (int) $p->product_id === (int) $pid,
                'global'  => true,   // baseline — applies to every UUC code
                default   => false,
            };
        });

        // Most specific first (codes > batch > product > global); among equals,
        // a locked policy wins so an explicit lock is never overridden.
        $rank = ['codes' => 4, 'batch' => 3, 'product' => 2, 'global' => 1];
        return $matches->sort(function ($a, $b) use ($rank) {
            $ra = $rank[$a->scope_type] ?? 0;
            $rb = $rank[$b->scope_type] ?? 0;
            return $ra !== $rb ? $rb <=> $ra : ($b->locked <=> $a->locked);
        })->first();
    }
}
