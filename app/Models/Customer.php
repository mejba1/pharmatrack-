<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer — reuses the existing `distributors` table. Represents anyone we
 * sell product to (distributor, retailer, hospital, pharmacy …).
 */
class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'distributors';

    protected $fillable = [
        'customer_code', 'parent_id', 'country_id', 'manager_id', 'name', 'company_name', 'type',
        'license_number', 'gmp_certificate_number', 'license_expiry',
        'contact_person', 'contact_email', 'contact_phone', 'address', 'status',
    ];

    protected $casts = [
        'license_expiry' => 'date',
    ];

    public const TYPES = [
        'national_distributor' => 'National Distributor',
        'regional_distributor' => 'Regional Distributor',
        'sub_distributor'      => 'Sub Distributor',
        'retailer'             => 'Retailer',
        'pharmacy'             => 'Pharmacy',
        'hospital'             => 'Hospital',
        'manufacturer'         => 'Manufacturer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────
    public function country()  { return $this->belongsTo(Country::class); }
    public function manager()  { return $this->belongsTo(User::class, 'manager_id'); }
    public function sales()     { return $this->hasMany(CustomerSale::class, 'customer_id'); }
    public function soldUnits()  { return $this->hasMany(BatchUnit::class, 'sold_to_id'); }

    // ── Accessors ──────────────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active'             => 'badge-approved',
            'suspended', 'expired' => 'badge-cancelled',
            default              => 'badge-pending',
        };
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    /** Next sequential customer code, e.g. CUS-000042. */
    public static function nextCode(): string
    {
        $last = static::withTrashed()->where('customer_code', 'like', 'CUS-%')
            ->orderByDesc('customer_code')->value('customer_code');
        $seq = $last ? ((int) substr($last, 4) + 1) : 1;
        return 'CUS-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
