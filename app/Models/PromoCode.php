<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class PromoCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'description', 'scope', 'discount_type', 'discount_value',
        'product_id', 'min_order_value', 'max_discount', 'usage_limit',
        'used_count', 'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'discount_value'  => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'max_discount'    => 'decimal:2',
        'usage_limit'     => 'integer',
        'used_count'      => 'integer',
        'starts_at'       => 'date',
        'ends_at'         => 'date',
        'is_active'       => 'boolean',
    ];

    public const SCOPES = [
        'total'   => 'Whole order total',
        'product' => 'Specific product',
        'none'    => 'No discount (tracking only)',
    ];

    public const TYPES = [
        'percent' => 'Percentage %',
        'fixed'   => 'Fixed amount',
    ];

    public function product() { return $this->belongsTo(Product::class); }

    /** Normalise codes to a canonical uppercase form. */
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim((string) $value));
    }

    /** Find a usable code by its string, or null. */
    public static function findUsable(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))->first();
    }

    /**
     * Generate a random, unique promo code. Uses an unambiguous alphabet
     * (no 0/O/1/I/L). $exclude avoids collisions within a not-yet-persisted batch.
     */
    public static function generateCode(string $prefix = '', array $exclude = [], int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $prefix = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($prefix)));

        do {
            $rand = '';
            for ($i = 0; $i < $length; $i++) {
                $rand .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = $prefix !== '' ? "{$prefix}-{$rand}" : $rand;
        } while (in_array($code, $exclude, true) || static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Why this code cannot be used right now, or null when it's valid.
     * $orderValue lets us enforce the minimum-order rule when a value is known.
     */
    public function invalidReason(?float $orderValue = null): ?string
    {
        if (! $this->is_active)                                   return 'This promo code is no longer active.';
        if ($this->starts_at && $this->starts_at->isFuture())     return 'This promo code is not active yet.';
        if ($this->ends_at && $this->ends_at->endOfDay()->isPast()) return 'This promo code has expired.';
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) return 'This promo code has reached its usage limit.';
        if ($orderValue !== null && $this->min_order_value && $orderValue < (float) $this->min_order_value) {
            return 'Order does not meet the minimum value for this code.';
        }

        return null;
    }

    public function isUsable(?float $orderValue = null): bool
    {
        return $this->invalidReason($orderValue) === null;
    }

    /** Discount applied to a given base amount, honouring type and cap. */
    public function discountOn(float $base): float
    {
        if ($this->scope === 'none' || $base <= 0) return 0.0;

        $amount = $this->discount_type === 'percent'
            ? $base * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        if ($this->max_discount) $amount = min($amount, (float) $this->max_discount);

        return round(min($amount, $base), 2);
    }

    public function getLabelAttribute(): string
    {
        if ($this->scope === 'none') return 'No discount';
        $val = $this->discount_type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->discount_value, 2), '0'), '.') . '%'
            : number_format((float) $this->discount_value, 2);

        return $this->scope === 'product'
            ? "{$val} off " . ($this->product?->name ?? 'a product')
            : "{$val} off order total";
    }

    public function getScopeLabelAttribute(): string
    {
        return self::SCOPES[$this->scope] ?? $this->scope;
    }
}
