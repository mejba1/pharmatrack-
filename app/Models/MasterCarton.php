<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class MasterCarton extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'consignment_id', 'product_id', 'batch_id', 'carton_number', 'qr_code', 'carton_type', 'label',
        'capacity', 'packed_quantity', 'serial_start', 'serial_end',
        'status', 'carton_condition', 'condition_note', 'evidence_path', 'received_location',
        'dispatched_at', 'received_at', 'notes',
    ];

    protected $casts = [
        'capacity'        => 'integer',
        'packed_quantity' => 'integer',
        'serial_start'    => 'integer',
        'serial_end'      => 'integer',
        'dispatched_at'   => 'datetime',
        'received_at'     => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function consignment()
    {
        return $this->belongsTo(Consignment::class);
    }

    public function scans()
    {
        return $this->hasMany(MasterCartonScan::class)->latest();
    }

    public function contents()
    {
        return $this->hasMany(MasterCartonContent::class)->orderBy('id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getIsPackedAttribute(): bool
    {
        return $this->packed_quantity > 0;
    }

    /** Remaining capacity available for more contents. */
    public function getRemainingCapacityAttribute(): int
    {
        return max(0, (int) $this->capacity - (int) $this->packed_quantity);
    }

    /** True when the carton holds more than one distinct product or batch. */
    public function getIsMixedAttribute(): bool
    {
        $c = $this->relationLoaded('contents') ? $this->contents : $this->contents()->get();
        return $c->pluck('product_id')->unique()->count() > 1
            || $c->pluck('batch_id')->unique()->count() > 1;
    }

    public function getProductsSummaryAttribute(): string
    {
        $c = $this->relationLoaded('contents') ? $this->contents : $this->contents()->with('product')->get();
        if ($c->isEmpty()) {
            return $this->product?->name ?? '—';
        }
        $names = $c->pluck('product.name')->filter()->unique()->values();
        return $names->count() <= 1 ? ($names->first() ?? '—') : 'Mixed · ' . $names->count() . ' products';
    }

    public function getBatchesSummaryAttribute(): string
    {
        $c = $this->relationLoaded('contents') ? $this->contents : $this->contents()->with('batch')->get();
        if ($c->isEmpty()) {
            return $this->batch?->brn ?? '—';
        }
        $brns = $c->pluck('batch.brn')->filter()->unique()->values();
        return $brns->count() <= 1 ? ($brns->first() ?? '—') : 'Mixed · ' . $brns->count() . ' batches';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'quality_checked'    => 'Quality Checked',
            'ready_for_dispatch' => 'Ready for Dispatch',
            'in_transit'         => 'In Transit',
            'received_ho'        => 'Received at Head Office',
            'received_depot'     => 'Received at Depot',
            default              => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'received', 'received_ho', 'received_depot', 'closed' => 'badge-delivered',
            'quality_checked'                                     => 'badge-approved',
            'ready_for_dispatch', 'dispatched'                    => 'badge-shipped',
            'in_transit', 'packed'                                => 'badge-pending',
            'damaged'                                             => 'badge-hold',
            'returned'                                            => 'badge-cancelled',
            default                                               => 'badge-draft', // created
        };
    }

    public function getConditionLabelAttribute(): string
    {
        return ucfirst($this->carton_condition ?? 'good');
    }

    public function getConditionBadgeClassAttribute(): string
    {
        return match ($this->carton_condition) {
            'damaged'  => 'badge-hold',
            'missing'  => 'badge-cancelled',
            'returned' => 'badge-pending',
            default    => 'badge-active', // good
        };
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        return $this->evidence_path ? asset('storage/' . $this->evidence_path) : null;
    }

    public function getSerialRangeAttribute(): string
    {
        if (!$this->is_packed) {
            return '—';
        }
        $c = $this->relationLoaded('contents') ? $this->contents : $this->contents()->get();
        if ($c->count() === 1) {
            return "{$c->first()->serial_start}–{$c->first()->serial_end}";
        }
        if ($this->serial_start !== null) {
            return "{$this->serial_start}–{$this->serial_end}";
        }
        return 'Multiple';
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Recalculate carton summary fields from its contents:
     * packed_quantity, homogeneous product/batch/serial range (or NULL when
     * mixed), and packed/created status. Caller persists the result.
     */
    public function recomputeFromContents(): void
    {
        $contents = $this->contents()->get();

        $this->packed_quantity = (int) $contents->sum('quantity');

        $products = $contents->pluck('product_id')->unique();
        $batches  = $contents->pluck('batch_id')->unique();

        if ($contents->isEmpty()) {
            $this->product_id = $this->carton_type === 'generic' ? null : $this->product_id;
            $this->batch_id   = $this->carton_type === 'generic' ? null : $this->batch_id;
            $this->serial_start = null;
            $this->serial_end   = null;
        } elseif ($products->count() === 1 && $batches->count() === 1) {
            // Homogeneous — keep a tidy summary on the carton itself.
            $this->product_id   = $products->first();
            $this->batch_id     = $batches->first();
            $this->serial_start = (int) $contents->min('serial_start');
            $this->serial_end   = (int) $contents->max('serial_end');
        } else {
            // Mixed — summary lives in contents only.
            $this->product_id   = null;
            $this->batch_id     = null;
            $this->serial_start = null;
            $this->serial_end   = null;
        }

        // Keep status in step with packing, without overriding movement states.
        $movementStates = ['dispatched', 'in_transit', 'received', 'received_ho', 'received_depot', 'damaged', 'returned', 'closed'];
        if (!in_array($this->status, $movementStates, true)) {
            $this->status = $this->packed_quantity > 0 ? 'packed' : 'created';
        }
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        // Product / batch live on the carton itself for homogeneous cartons, but
        // are NULL for mixed and unpacked-generic cartons — match the contents
        // too so those cartons aren't invisible to the filter.
        if (!empty($filters['product_id'])) {
            $pid = $filters['product_id'];
            $query->where(fn (Builder $w) => $w
                ->where('product_id', $pid)
                ->orWhereHas('contents', fn ($c) => $c->where('product_id', $pid)));
        }
        if (!empty($filters['batch_id'])) {
            $bid = $filters['batch_id'];
            $query->where(fn (Builder $w) => $w
                ->where('batch_id', $bid)
                ->orWhereHas('contents', fn ($c) => $c->where('batch_id', $bid)));
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        // Pack-fill status (empty / partially packed / full).
        if (!empty($filters['fill'])) {
            match ($filters['fill']) {
                'empty'   => $query->where('packed_quantity', 0),
                'partial' => $query->where('packed_quantity', '>', 0)->whereColumn('packed_quantity', '<', 'capacity'),
                'full'    => $query->where('packed_quantity', '>', 0)->whereColumn('packed_quantity', '>=', 'capacity'),
                default   => $query,
            };
        }
        // Created date range.
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        // Explicit carton id set (multi-select bulk label download).
        if (!empty($filters['ids'])) {
            $ids = is_array($filters['ids']) ? $filters['ids'] : explode(',', (string) $filters['ids']);
            $ids = array_filter(array_map('intval', $ids));
            if ($ids) {
                $query->whereIn('id', $ids);
            }
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function (Builder $w) use ($s) {
                $w->where('carton_number', 'like', "%{$s}%")
                  ->orWhere('qr_code', 'like', "%{$s}%")
                  ->orWhere('label', 'like', "%{$s}%")
                  ->orWhereHas('batch', fn ($b) => $b->where('brn', 'like', "%{$s}%")
                                                     ->orWhere('batch_number', 'like', "%{$s}%"))
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('contents.product', fn ($p) => $p->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('contents.batch', fn ($b) => $b->where('brn', 'like', "%{$s}%")
                                                              ->orWhere('batch_number', 'like', "%{$s}%"));
            });
        }
        return $query;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Next global carton number, e.g. MC-000001. */
    public static function generateCartonNumber(): string
    {
        $last = static::withTrashed()->orderByDesc('id')->value('carton_number');
        $seq  = $last ? ((int) substr($last, 3) + 1) : 1;

        return 'MC-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
