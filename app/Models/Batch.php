<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Batch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'brn', 'batch_number', 'lot_number',
        'manufacture_date', 'expiry_date',
        'quantity_produced', 'quantity_available',
        'manufacturing_site', 'manufacturing_country',
        'qc_status', 'qc_approved_by', 'qc_approval_date',
        'coa_document_path', 'storage_conditions',
        'storage_temp_min', 'storage_temp_max',
        'status', 'notes',
    ];

    protected $casts = [
        'manufacture_date'   => 'date',
        'expiry_date'        => 'date',
        'qc_approval_date'   => 'date',
        'quantity_produced'  => 'integer',
        'quantity_available' => 'integer',
        'storage_temp_min'   => 'decimal:2',
        'storage_temp_max'   => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function units()
    {
        return $this->hasMany(BatchUnit::class);
    }

    public function unitLogs()
    {
        return $this->hasMany(BatchUnitLog::class)->latest();
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getCoaUrlAttribute(): ?string
    {
        return $this->coa_document_path
            ? Storage::disk('public')->url($this->coa_document_path)
            : null;
    }

    public function getCoaNameAttribute(): ?string
    {
        return $this->coa_document_path ? basename($this->coa_document_path) : null;
    }

    /** Days until expiry (negative if already expired). */
    public function getDaysToExpiryAttribute(): ?int
    {
        return $this->expiry_date
            ? now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false)
            : null;
    }

    public function getQcStatusLabelAttribute(): string
    {
        return ucfirst($this->qc_status);
    }

    public function getQcBadgeClassAttribute(): string
    {
        return match ($this->qc_status) {
            'released'   => 'badge-approved',
            'pending'    => 'badge-pending',
            'quarantine' => 'badge-pending',
            'rejected'   => 'badge-cancelled',
            'recalled'   => 'badge-cancelled',
            default      => 'badge-pending',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active'     => 'badge-approved',
            'expired'    => 'badge-cancelled',
            'recalled'   => 'badge-cancelled',
            'quarantine' => 'badge-pending',
            'depleted'   => 'badge-pending',
            default      => 'badge-pending',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function (Builder $w) use ($s) {
                $w->where('brn', 'like', "%{$s}%")
                  ->orWhere('batch_number', 'like', "%{$s}%")
                  ->orWhere('lot_number', 'like', "%{$s}%")
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%"));
            });
        }
        if (!empty($filters['qc_status'])) {
            $query->where('qc_status', $filters['qc_status']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['expiry'])) {
            $days = (int) $filters['expiry'];
            $query->whereDate('expiry_date', '>=', now())
                  ->whereDate('expiry_date', '<=', now()->addDays($days));
        }
        return $query;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Auto-generate the next Batch Registration Number.
     * Format: BRN-{PRODUCT_ID}-{YYMM}-{NNN}
     */
    public static function generateBrn(int $productId): string
    {
        $prefix = 'BRN-' . str_pad((string) $productId, 5, '0', STR_PAD_LEFT) . '-' . date('ym') . '-';
        $last   = static::withTrashed()
                        ->where('brn', 'like', $prefix . '%')
                        ->orderByDesc('brn')
                        ->value('brn');

        $seq = $last ? ((int) substr($last, -3) + 1) : 1;

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
