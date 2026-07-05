<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Default verification-page configuration (used when nothing is saved). */
    public static function defaults(): array
    {
        return [
            'verify_style'     => 'style1',
            'brand_name'       => 'PharmaTrack',
            'logo_path'        => null,
            'bg_image'         => null,
            'primary'          => '#059669',
            'button_color'     => '#059669',
            'footer_color'     => '#94a3b8',
            'report_form'      => true,
            'info_form'        => true,
            'verify_button'    => true,
            'verify_button_text' => 'Verify authenticity',
            'show_product_photo' => true,
            'verify_code_panel'  => true,
            'show_journey'       => true,
            'history_limit'      => 10,
            'show_country'       => true,
            'show_city'          => true,
            'show_ip'            => false,
            'show_device'        => true,
            'show_verification'  => true,
            'show_leaflet'       => false,
            'leaflet_path'       => null,
            'leaflet_label'      => 'Download insert / leaflet',
            'verify_button_pos'  => 'hero',
            'card_left_label'    => 'Batch',
            'card_left_field'    => 'batch',
            'card_right_label'   => 'Expires in',
            'card_right_field'   => 'expires_in',
            'loader_enabled'   => true,
            'loader_style'     => 'spinner',
            'loader_text'      => 'Verifying authenticity…',
            'loader_min_ms'    => 700,
            'loader_image'     => null,
            'genuine_title'    => 'Verified Authentic',
            'genuine_subtitle' => '',
            'footer'           => 'Protected by PharmaTrack Anti-Counterfeit',
            'fields'           => ['product', 'generic', 'strength', 'batch', 'manufactured', 'expires', 'serial', 'manufacturer', 'country'],
            'messages'         => [
                'fake'     => ['title' => 'This is NOT a Genuine Product', 'text' => "This code doesn't match any product in our records. It may be counterfeit — do not use it."],
                'recalled' => ['title' => 'Product Recalled', 'text' => 'This product has been recalled. Do not use it and contact the manufacturer immediately.'],
                'locked'   => ['title' => 'Locked by Administrator', 'text' => 'This product can only be shown by the administrator. No further information is available.'],
                'expired'  => ['title' => 'Product Expired', 'text' => 'This product has passed its expiry date and should not be used.'],
                'invalid'  => ['title' => 'Not Valid for Sale', 'text' => 'This unit is marked not valid for sale.'],
                'country'  => ['title' => 'May Be Counterfeit', 'text' => 'This product is authorized for sale only in other countries. If you bought it in your country, it may be counterfeit — please report it.'],
                'city'     => ['title' => 'Not Sold in Your City', 'text' => 'This product is not authorized for sale in your city. If you bought it here, it may be counterfeit — please report it.'],
                'device'   => ['title' => 'Device Limit Reached', 'text' => 'This product has reached its allowed number of verification devices.'],
                'hit'      => ['title' => 'Verification Limit Reached', 'text' => 'This code has reached its maximum number of verifications.'],
                'vpn'      => ['title' => 'VPN / Proxy Detected', 'text' => 'Verification is not available over a VPN or proxy network. Please turn off your VPN and try again.'],
                'ip'       => ['title' => 'Access Not Allowed', 'text' => 'Verification from your network is not permitted. If you believe this is an error, please contact the manufacturer.'],
                'rate'     => ['title' => 'Too Many Attempts', 'text' => 'Too many scans were detected in a short period of time. Suspicious activity has been logged. Please try again later.'],
            ],
            'scan_intelligence' => static::scanIntelligenceDefaults(),
        ];
    }

    /**
     * Default per-rule configuration for the 8 scan-intelligence scenarios.
     * Each rule: enabled, threshold(s), customer-facing title + text, how many
     * previous records to show, and whether to show the report form.
     */
    public static function scanIntelligenceDefaults(): array
    {
        return [
            'multi_country' => [
                'enabled' => true, 'threshold' => 2, 'records' => 3, 'report' => true,
                'title' => 'Scanned from Multiple Countries',
                'text'  => 'This product has been scanned from multiple countries. Please verify that you are purchasing from an authorized seller.',
            ],
            'repeat_ip_block' => [
                'enabled' => true, 'threshold' => 5, 'records' => 5, 'report' => true,
                'title' => 'Multiple Scans From Your Device',
                'text'  => 'This product has been scanned multiple times from your device. The system has automatically blocked further attempts. Please contact the administrator at info@beaconpharma.com.bd for assistance.',
            ],
            'multi_ip_diff_country' => [
                'enabled' => true, 'threshold' => 3, 'records' => 3, 'report' => false,
                'title' => 'Scanned From Different Networks',
                'text'  => 'This product has been scanned from different devices or networks across various regions. Please ensure authenticity before purchase.',
            ],
            'multi_ip_same_country' => [
                'enabled' => true, 'threshold' => 3, 'records' => 3, 'report' => false,
                'title' => 'Scanned From Multiple Devices',
                'text'  => 'This product has been scanned from multiple devices within the same country. Please confirm you are purchasing from an authorized seller.',
            ],
            'same_ip_autolock' => [
                'enabled' => true, 'threshold' => 8, 'records' => 5, 'report' => true,
                'title' => 'Locked For Security',
                'text'  => 'This product has been scanned repeatedly from your device and is now locked for security reasons (possible counterfeit activity detected).',
            ],
            'recent_3day' => [
                'enabled' => true, 'days' => 3, 'records' => 5, 'report' => true,
                'title' => 'Already Scanned Recently',
                'text'  => 'You have already scanned this product within the last few days. Why do you need to check again?',
            ],
            'high_freq' => [
                'enabled' => true, 'window_min' => 10, 'max' => 3, 'records' => 0, 'report' => false,
                'title' => 'Suspicious Activity Detected',
                'text'  => 'Too many scans detected in a short period of time. Suspicious activity logged.',
            ],
            'region_mismatch' => [
                'enabled' => true, 'records' => 0, 'report' => true,
                'title' => 'Not Intended For Your Region',
                'text'  => 'This product is not intended for use in your region. Please check with the official distributor.',
            ],
            'returning_scan' => [
                'enabled' => true, 'days' => 10, 'records' => 3, 'report' => true,
                'title' => 'Welcome Back',
                'text'  => 'You scanned this product some days ago. If you need a confirmation document, fill in your details to download a genuine-product certificate.',
            ],
        ];
    }

    /** Human labels + descriptions for the scan-intelligence admin tab. */
    public static function scanIntelligenceMeta(): array
    {
        return [
            'multi_country'         => ['label' => 'Multiple Country Detections', 'fields' => ['threshold' => 'Min. distinct countries']],
            'repeat_ip_block'       => ['label' => 'Repeated Scans (Same IP) — Block', 'fields' => ['threshold' => 'Scans from same IP to block']],
            'multi_ip_diff_country' => ['label' => 'Multiple IPs — Different Countries', 'fields' => ['threshold' => 'Min. distinct IPs']],
            'multi_ip_same_country' => ['label' => 'Multiple IPs — Same Country', 'fields' => ['threshold' => 'Min. distinct IPs']],
            'same_ip_autolock'      => ['label' => 'Same IP — Auto Lock', 'fields' => ['threshold' => 'Scans from same IP to lock']],
            'recent_3day'           => ['label' => 'Already Scanned (Recently)', 'fields' => ['days' => 'Within last N days']],
            'high_freq'             => ['label' => 'High-Frequency Scans', 'fields' => ['max' => 'Max scans allowed', 'window_min' => 'Within N minutes']],
            'region_mismatch'       => ['label' => 'Location / Region Mismatch', 'fields' => []],
            'returning_scan'        => ['label' => 'Returning Scan + Certificate', 'fields' => ['days' => 'Returns after N+ days']],
        ];
    }

    /** Field labels shown in the admin "fields to display" toggles. */
    public static function fieldLabels(): array
    {
        return [
            'product' => 'Product', 'generic' => 'Generic', 'strength' => 'Strength / Form',
            'batch' => 'Batch No.', 'manufactured' => 'Manufactured', 'expires' => 'Expires',
            'serial' => 'Serial No.', 'manufacturer' => 'Manufacturer', 'country' => 'Country',
        ];
    }

    /**
     * Ready-made colour themes for the verification page. Each applies the
     * trio of colours + the verify-button position. Picked from settings.
     */
    public static function templates(): array
    {
        return [
            'emerald' => [
                'label' => 'Emerald (default)',
                'values' => ['primary' => '#059669', 'button_color' => '#059669', 'footer_color' => '#94a3b8', 'verify_button_pos' => 'hero'],
            ],
            'ocean' => [
                'label' => 'Ocean Blue',
                'values' => ['primary' => '#2563eb', 'button_color' => '#1d4ed8', 'footer_color' => '#94a3b8', 'verify_button_pos' => 'hero'],
            ],
            'royal' => [
                'label' => 'Royal Purple',
                'values' => ['primary' => '#7c3aed', 'button_color' => '#6d28d9', 'footer_color' => '#a78bda', 'verify_button_pos' => 'card'],
            ],
            'sunset' => [
                'label' => 'Sunset Amber',
                'values' => ['primary' => '#ea580c', 'button_color' => '#c2410c', 'footer_color' => '#b9a08f', 'verify_button_pos' => 'card'],
            ],
            'midnight' => [
                'label' => 'Midnight Slate',
                'values' => ['primary' => '#0f172a', 'button_color' => '#334155', 'footer_color' => '#94a3b8', 'verify_button_pos' => 'hero'],
            ],
        ];
    }

    /** Wipe all saved overrides so the built-in defaults take effect again. */
    public static function resetAll(): void
    {
        static::query()->delete();
        static::forget();
    }

    /** Data sources selectable for the two highlight-card slots. */
    public static function cardFieldOptions(): array
    {
        return [
            'none'         => '— Hide —',
            'product'      => 'Product name',
            'generic'      => 'Generic name',
            'strength'     => 'Strength / Form',
            'batch'        => 'Batch (BRN)',
            'batch_number' => 'Batch number',
            'manufactured' => 'Manufactured date',
            'expires'      => 'Expiry date',
            'expires_in'   => 'Expires in (months)',
            'serial'       => 'Serial number',
            'manufacturer' => 'Manufacturer',
            'country'      => 'Country of origin',
        ];
    }

    /** Merged config (defaults + saved overrides), cached. */
    public static function config(): array
    {
        return Cache::remember('ac_settings', 3600, function () {
            $d  = static::defaults();
            $db = static::pluck('value', 'key')->toArray();
            $cfg = $d;
            foreach ($db as $k => $v) {
                if (in_array($k, ['fields', 'messages', 'scan_intelligence'], true)) {
                    $cfg[$k] = json_decode((string) $v, true) ?: $d[$k];
                } elseif (in_array($k, ['report_form', 'info_form', 'verify_button', 'show_product_photo', 'verify_code_panel', 'show_journey', 'show_country', 'show_city', 'show_ip', 'show_device', 'show_verification', 'show_leaflet', 'loader_enabled'], true)) {
                    $cfg[$k] = (bool) $v;
                } else {
                    $cfg[$k] = $v;
                }
            }
            $cfg['messages'] = array_replace_recursive($d['messages'], $cfg['messages'] ?? []);
            $cfg['scan_intelligence'] = array_replace_recursive($d['scan_intelligence'], $cfg['scan_intelligence'] ?? []);
            if (empty($cfg['primary'])) $cfg['primary'] = $d['primary'];
            return $cfg;
        });
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value]);
    }

    public static function forget(): void
    {
        Cache::forget('ac_settings');
    }

    /** Darken a hex colour by a percentage (for gradient end / hover). */
    public static function darken(string $hex, float $pct = 0.14): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if (strlen($hex) !== 6) return '#047857';
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        return sprintf('#%02x%02x%02x', (int) ($r * (1 - $pct)), (int) ($g * (1 - $pct)), (int) ($b * (1 - $pct)));
    }

    /** Soft rgba() of a hex colour (tinted backgrounds / shadows). */
    public static function soft(string $hex, float $a = 0.12): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        if (strlen($hex) !== 6) return "rgba(5,150,105,$a)";
        return sprintf('rgba(%d,%d,%d,%s)', hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), $a);
    }
}
