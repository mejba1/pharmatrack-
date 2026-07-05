<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecall extends Model
{
    protected $fillable = [
        'recall_number', 'product_id', 'batch_id',
        'scope', 'country_code', 'severity', 'reason',
        'active', 'recalled_by', 'recalled_at', 'notes',
    ];

    protected $casts = [
        'active'      => 'boolean',
        'recalled_at' => 'datetime',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function batch()   { return $this->belongsTo(Batch::class); }

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope) {
            'global'  => 'Global Recall',
            'country' => 'Country Recall',
            default   => 'Batch Recall',
        };
    }

    /**
     * Is there an active recall affecting this unit for the given scan country?
     *
     *  • global  → every unit, everywhere
     *  • batch   → the unit's batch, every country
     *  • country → the unit's product, only when the scan country matches the
     *              recall's country_code (a null country_code = the whole product)
     */
    public static function activeForUnit(?BatchUnit $unit, ?string $countryCode = null): ?self
    {
        if (!$unit) return null;

        $pid = $unit->batch?->product_id;
        $cc  = $countryCode ? strtoupper($countryCode) : null;

        return static::where('active', true)
            ->where(function ($q) use ($unit, $pid, $cc) {
                $q->where('scope', 'global')
                  ->orWhere(fn ($w) => $w->where('scope', 'batch')->where('batch_id', $unit->batch_id))
                  ->orWhere(function ($w) use ($pid, $cc) {
                      $w->where('scope', 'country')->where('product_id', $pid)
                        ->where(function ($x) use ($cc) {
                            $x->whereNull('country_code');          // whole-product recall
                            if ($cc) $x->orWhere('country_code', $cc);
                        });
                  });
            })
            ->latest('recalled_at')
            ->first();
    }

    public static function nextNumber(): string
    {
        $prefix = 'RCL-' . now()->format('Ymd') . '-';
        $last   = static::where('recall_number', 'like', $prefix . '%')
                        ->orderByDesc('recall_number')
                        ->value('recall_number');
        $seq = $last ? ((int) substr($last, -3) + 1) : 1;
        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
