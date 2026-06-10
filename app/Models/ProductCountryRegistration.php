<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCountryRegistration extends Model
{
    protected $fillable = [
        'product_id',
        'country_id',
        'local_registration_number',
        'registration_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'expiry_date'       => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date
            && !$this->expiry_date->isPast()
            && $this->expiry_date->diffInDays(now()) <= 90;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'badge-approved',
            'pending'  => 'badge-pending',
            'rejected' => 'badge-cancelled',
            'expired'  => 'badge-cancelled',
            default    => 'badge-pending',
        };
    }
}
