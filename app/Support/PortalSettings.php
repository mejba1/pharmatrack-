<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Super-admin-controlled configuration for the customer portal — what shows
 * and what customers may do. Stored in the settings table (portal_* keys).
 */
class PortalSettings
{
    /** Setting key => default value. Booleans are stored as 0/1. */
    public static function defaults(): array
    {
        return [
            'portal_enabled'            => true,   // master switch
            'portal_show_orders'        => true,
            'portal_show_invoices'      => true,
            'portal_show_documents'     => true,
            'portal_show_units'         => true,
            'portal_allow_ordering'     => true,   // "Place Order"
            'portal_allow_registration' => true,   // self-registration
            'portal_allow_profile_edit' => true,   // profile self-edit
            'portal_welcome_message'    => '',     // banner on the dashboard
            'portal_support_email'      => '',      // shown on login/portal
        ];
    }

    /** All resolved settings (saved values merged over defaults), cached. */
    public static function all(): array
    {
        return Cache::remember('portal_settings', 3600, function () {
            $defaults = static::defaults();
            $saved    = Setting::whereIn('key', array_keys($defaults))->pluck('value', 'key')->all();

            $out = $defaults;
            foreach ($defaults as $key => $default) {
                if (array_key_exists($key, $saved)) {
                    $out[$key] = is_bool($default) ? (bool) (int) $saved[$key] : (string) $saved[$key];
                }
            }

            return $out;
        });
    }

    public static function get(string $key): mixed
    {
        return static::all()[$key] ?? null;
    }

    /** Persist submitted settings and clear the cache. */
    public static function save(array $data): void
    {
        foreach (static::defaults() as $key => $default) {
            if (is_bool($default)) {
                Setting::put($key, ! empty($data[$key]) ? 1 : 0);
            } else {
                Setting::put($key, (string) ($data[$key] ?? ''));
            }
        }

        Cache::forget('portal_settings');
    }
}
