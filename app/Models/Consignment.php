<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Consignment (internal "Shipment") — parent aggregation over many Master
 * Cartons for Factory → Depot distribution.
 */
class Consignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'consignment_number', 'qr_code', 'origin', 'destination', 'carrier',
        'vehicle_no', 'status', 'dispatched_at', 'received_at', 'notes',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'received_at'   => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function cartons()
    {
        return $this->hasMany(MasterCarton::class)->orderBy('carton_number');
    }

    public function scans()
    {
        return $this->hasMany(ConsignmentScan::class)->latest();
    }

    // ── Aggregate accessors (scan-the-parent summary) ─────────────────────

    public function getCartonCountAttribute(): int
    {
        return $this->relationLoaded('cartons') ? $this->cartons->count() : $this->cartons()->count();
    }

    public function getTotalUnitsAttribute(): int
    {
        $cartons = $this->relationLoaded('cartons') ? $this->cartons : $this->cartons()->get();
        return (int) $cartons->sum('packed_quantity');
    }

    /** Distinct product names carried by the consignment. */
    public function getProductListAttribute()
    {
        return $this->loadedCartonsWithContents()
            ->flatMap(fn ($c) => $c->contents->pluck('product.name'))
            ->filter()->unique()->sort()->values();
    }

    /** Distinct batch references carried by the consignment. */
    public function getBatchListAttribute()
    {
        return $this->loadedCartonsWithContents()
            ->flatMap(fn ($c) => $c->contents->pluck('batch.brn'))
            ->filter()->unique()->sort()->values();
    }

    private function loadedCartonsWithContents()
    {
        if ($this->relationLoaded('cartons') && $this->cartons->every(fn ($c) => $c->relationLoaded('contents'))) {
            return $this->cartons;
        }
        return $this->cartons()->with('contents.product', 'contents.batch')->get();
    }

    // ── Receiving reconciliation (expected vs received) ───────────────────

    public function getReceivedCartonCountAttribute(): int
    {
        $cartons = $this->relationLoaded('cartons') ? $this->cartons : $this->cartons()->get();
        return $cartons->where('status', 'received')->count();
    }

    public function getMissingCartonsAttribute()
    {
        $cartons = $this->relationLoaded('cartons') ? $this->cartons : $this->cartons()->get();
        return $cartons->where('status', '!=', 'received')->pluck('carton_number')->values();
    }

    // ── Status presentation ───────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'in_transit' => 'In Transit',
            default      => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'received', 'closed'       => 'badge-approved',
            'dispatched', 'in_transit' => 'badge-pending',
            default                    => 'badge-cancelled',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['destination'])) {
            $query->where('destination', 'like', "%{$filters['destination']}%");
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function (Builder $w) use ($s) {
                $w->where('consignment_number', 'like', "%{$s}%")
                  ->orWhere('qr_code', 'like', "%{$s}%")
                  ->orWhere('destination', 'like', "%{$s}%")
                  ->orWhereHas('cartons', fn ($c) => $c->where('carton_number', 'like', "%{$s}%"));
            });
        }
        return $query;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Next consignment number, e.g. SHP-2026-00001 (year-scoped sequence). */
    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = static::withTrashed()
            ->where('consignment_number', 'like', "SHP-{$year}-%")
            ->orderByDesc('id')->value('consignment_number');
        $seq  = $last ? ((int) Str::afterLast($last, '-') + 1) : 1;

        return 'SHP-' . $year . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    public static function generateQr(): string
    {
        do {
            $qr = 'SHP' . Str::upper(Str::random(10));
        } while (static::withTrashed()->where('qr_code', $qr)->exists());

        return $qr;
    }
}
