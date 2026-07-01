<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Customer — a first-class entity (own `customers` table). Represents anyone
 * we sell to: Agent, Distributor, GPSP or Local. Can self-login to a customer
 * portal/dashboard via the `customer` auth guard.
 */
class Customer extends Authenticatable
{
    use SoftDeletes, Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'customer_code', 'parent_id', 'country_id', 'manager_id', 'name', 'type',
        'email', 'phone', 'city', 'address', 'company_name', 'company_id',
        'identification_type', 'identification_number', 'referenced_by', 'company_logo',
        'license_number', 'gmp_certificate_number', 'license_expiry',
        'contact_person', 'contact_email', 'contact_phone', 'status', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'license_expiry'    => 'date',
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'password'          => 'hashed',
    ];

    /** Customer types. */
    public const TYPES = [
        'agent'       => 'Agent',
        'distributor' => 'Distributor',
        'gpsp'        => 'GPSP',
        'local'       => 'Local',
    ];

    /** Identification document types. */
    public const ID_TYPES = [
        'nid'           => 'National ID',
        'passport'      => 'Passport',
        'trade_license' => 'Trade License',
        'tin'           => 'TIN',
        'other'         => 'Other',
    ];

    // ── Relationships ──────────────────────────────────────────────────────
    public function country()  { return $this->belongsTo(Country::class); }
    public function manager()  { return $this->belongsTo(User::class, 'manager_id'); }
    public function sales()     { return $this->hasMany(CustomerSale::class, 'customer_id'); }
    public function soldUnits()  { return $this->hasMany(BatchUnit::class, 'sold_to_id'); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class, 'buyer_id'); }

    /** Send the customer-portal password-reset email (portal reset URL). */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\CustomerResetPassword($token));
    }

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

    public function getIdTypeLabelAttribute(): string
    {
        return self::ID_TYPES[$this->identification_type] ?? (string) $this->identification_type;
    }

    /** Public URL for the company logo, or null. */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->company_logo ? url('storage/' . ltrim($this->company_logo, '/')) : null;
    }

    /** Two-letter initials for an avatar fallback. */
    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];

        return strtoupper(collect($parts)->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: 'C';
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
