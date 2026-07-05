<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Staff in-app notification (topbar bell + Notifications page).
 * Maps to the existing `notifications` table.
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'severity', 'title', 'message', 'data',
        'notifiable_type', 'notifiable_id', 'action_url', 'action_label',
        'is_read', 'read_at', 'is_dismissed', 'dismissed_at',
    ];

    protected $casts = [
        'data'         => 'array',
        'is_read'      => 'boolean',
        'read_at'      => 'datetime',
        'is_dismissed' => 'boolean',
        'dismissed_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }

    /** Bootstrap-icon for the severity/type. */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'customer_registration' => 'bi-person-plus text-primary',
            'customer_order'        => 'bi-cart-plus text-success',
            default => match ($this->severity) {
                'critical' => 'bi-exclamation-octagon text-danger',
                'warning'  => 'bi-exclamation-triangle text-warning',
                default    => 'bi-info-circle text-primary',
            },
        };
    }

    // ── Creation helpers ──────────────────────────────────────────────────
    public static function add(int $userId, array $attrs): self
    {
        return static::create(array_merge([
            'user_id'      => $userId,
            'severity'     => 'info',
            'is_read'      => false,
            'is_dismissed' => false,
        ], $attrs));
    }

    /** Notify a set of staff users (deduplicated, skips nulls). */
    public static function pushToUsers(array $userIds, array $attrs): void
    {
        foreach (array_unique(array_filter($userIds)) as $uid) {
            static::add((int) $uid, $attrs);
        }
    }

    /** Notify every active super admin (plus any extra user ids given). */
    public static function pushToAdmins(array $attrs, array $extraUserIds = []): void
    {
        $admins = User::where('role', 'super_admin')->where('is_active', true)->pluck('id')->all();
        static::pushToUsers(array_merge($admins, $extraUserIds), $attrs);
    }
}
