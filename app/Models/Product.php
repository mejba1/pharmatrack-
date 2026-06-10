<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'prn',
        'name',
        'generic_name',
        'brand_name',
        'dosage_form',
        'strength',
        'pack_size',
        'atc_code',
        'hs_code',
        'controlled_substance',
        'manufacturer_name',
        'manufacturing_site',
        'country_of_origin',
        'shelf_life',
        'storage_conditions',
        'temperature_sensitivity',
        'unit_cost',
        'unit_of_measure',
        'status',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function countryRegistrations()
    {
        return $this->hasMany(ProductCountryRegistration::class);
    }

    public function approvedRegistrations()
    {
        return $this->hasMany(ProductCountryRegistration::class)
                    ->where('status', 'approved');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    /**
     * True if the product requires cold-chain or cool-chain storage.
     */
    public function getColdChainAttribute(): bool
    {
        return in_array($this->temperature_sensitivity, ['cold_chain', 'cool_chain', 'frozen']);
    }

    /**
     * Number of countries where this product has an approved registration.
     * Used when the relationship is not eager-loaded via withCount.
     */
    public function getApprovedCountriesCountAttribute(): int
    {
        return $this->approvedRegistrations()->count();
    }

    /**
     * Human-readable dosage form label.
     */
    public function getDosageFormLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->dosage_form));
    }

    /**
     * Human-readable status label with color class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active'            => 'badge-approved',
            'pending_approval'  => 'badge-pending',
            'discontinued'      => 'badge-cancelled',
            default             => 'badge-pending',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'            => 'Active',
            'pending_approval'  => 'Under Registration',
            'discontinued'      => 'Discontinued',
            default             => ucfirst($this->status),
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name',         'like', "%{$term}%")
              ->orWhere('prn',          'like', "%{$term}%")
              ->orWhere('generic_name', 'like', "%{$term}%")
              ->orWhere('brand_name',   'like', "%{$term}%");
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (!empty($filters['dosage_form'])) {
            $query->where('dosage_form', $filters['dosage_form']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Auto-generate the next PRN in sequence.
     * Format: PRN-{COUNTRY}-{FORM_ABBR}-{00001}
     */
    public static function generatePrn(string $countryCode = 'XX', string $formAbbr = 'GEN'): string
    {
        $prefix = strtoupper("PRN-{$countryCode}-{$formAbbr}-");
        $last   = static::withTrashed()
                         ->where('prn', 'like', $prefix . '%')
                         ->orderByDesc('prn')
                         ->value('prn');

        $seq = $last ? ((int) substr($last, -5) + 1) : 1;

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Dosage form abbreviations used in PRN generation.
     */
    public static function formAbbreviations(): array
    {
        return [
            'tablet'    => 'TAB',
            'capsule'   => 'CAP',
            'injection' => 'INJ',
            'syrup'     => 'SYR',
            'cream'     => 'CRM',
            'ointment'  => 'ONT',
            'drops'     => 'DRP',
            'inhaler'   => 'INH',
            'other'     => 'OTH',
        ];
    }
}
