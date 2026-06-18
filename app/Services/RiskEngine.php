<?php

namespace App\Services;

use App\Models\BatchUnit;
use App\Models\CountryAuthorization;
use App\Models\ProductRecall;
use App\Models\RiskAlert;
use App\Models\VerificationLog;
use App\Models\VerificationPolicy;

/**
 * Evaluates a freshly-created VerificationLog against the anti-counterfeit
 * rule set, creates RiskAlert records, and returns the cumulative risk score
 * (0–100). The caller persists the score + result back onto the log.
 */
class RiskEngine
{
    /** Points contributed by each rule category. */
    private const SCORES = [
        'geographic_impossibility' => 40,
        'impossible_travel'        => 40,
        'recalled_scan'            => 40,
        'destroyed_rejected'       => 40,
        'locked_scope'             => 40,
        'ip_blocked'               => 40,
        'vpn_blocked'              => 35,
        'unauthorized_market'      => 30,
        'unauthorized_city'        => 25,
        'excessive_scan_rate'      => 25,
        'scan_limit_exceeded'      => 25,
        'first_scan_mismatch'      => 25,
        'multiple_device'          => 20,
        'device_limit_exceeded'    => 20,
        'scan_burst'               => 18,
        'batch_cluster'            => 15,
        'expired_scan'             => 15,
        'vpn_proxy'                => 10,
    ];

    private const LEVELS = [
        'geographic_impossibility' => 'critical',
        'impossible_travel'        => 'critical',
        'recalled_scan'            => 'critical',
        'destroyed_rejected'       => 'critical',
        'locked_scope'             => 'critical',
        'ip_blocked'               => 'critical',
        'vpn_blocked'              => 'high',
        'unauthorized_market'      => 'high',
        'unauthorized_city'        => 'high',
        'excessive_scan_rate'      => 'high',
        'scan_limit_exceeded'      => 'high',
        'first_scan_mismatch'      => 'high',
        'multiple_device'          => 'high',
        'device_limit_exceeded'    => 'high',
        'scan_burst'               => 'high',
        'batch_cluster'            => 'medium',
        'expired_scan'             => 'medium',
        'vpn_proxy'                => 'medium',
    ];

    public function evaluate(VerificationLog $log, ?BatchUnit $unit, ?VerificationPolicy $policy = null): int
    {
        $hits  = [];   // category => description
        $uuc   = $log->uuc_code;
        $now   = $log->created_at ?? now();

        // History of this UUC, newest first (excluding the current log).
        $history = VerificationLog::where('uuc_code', $uuc)
            ->where('id', '!=', $log->id)
            ->latest()
            ->limit(200)
            ->get();

        // 9.1 — Geographic impossibility: different country within 24h.
        if ($log->country_code) {
            $recent = $history->filter(fn ($h) => $h->created_at && $h->created_at->gt($now->copy()->subDay()));
            $otherCountry = $recent->first(fn ($h) => $h->country_code && $h->country_code !== $log->country_code);
            if ($otherCountry) {
                $hits['geographic_impossibility'] = "Also scanned in {$otherCountry->country} within 24h (now {$log->country}).";

                // 9.7 — Impossible travel speed (needs coords + time gap).
                if ($log->latitude && $log->longitude && $otherCountry->latitude && $otherCountry->longitude) {
                    $km    = $this->haversineKm((float) $log->latitude, (float) $log->longitude, (float) $otherCountry->latitude, (float) $otherCountry->longitude);
                    $hours = max(0.01, $otherCountry->created_at->diffInMinutes($now) / 60);
                    if ($km / $hours > 900) { // faster than a commercial jet
                        $hits['impossible_travel'] = round($km) . "km in " . round($hours, 1) . "h — physically impossible.";
                    }
                }
            }
        }

        // 9.2 — Excessive scan rate: >10 in 7 days.
        $weekCount = $history->filter(fn ($h) => $h->created_at && $h->created_at->gt($now->copy()->subDays(7)))->count() + 1;
        if ($weekCount > 10) {
            $hits['excessive_scan_rate'] = "{$weekCount} scans in the last 7 days.";
        }

        // 9.11 — Scan burst: >50 of this UUC in 5 minutes.
        $burst = $history->filter(fn ($h) => $h->created_at && $h->created_at->gt($now->copy()->subMinutes(5)))->count() + 1;
        if ($burst > 50) {
            $hits['scan_burst'] = "{$burst} scans within 5 minutes.";
        }

        // 9.8 — Multiple device detection: distinct UA fingerprints within 1h.
        $hourLogs = $history->filter(fn ($h) => $h->created_at && $h->created_at->gt($now->copy()->subHour()));
        $devices  = $hourLogs->pluck('user_agent')->push($log->user_agent)->filter()->unique();
        if ($devices->count() >= 3) {
            $hits['multiple_device'] = "{$devices->count()} different devices within 1 hour.";
        }

        // 9.9 — VPN / proxy.
        if ($log->is_proxy) {
            $hits['vpn_proxy'] = "Verification via VPN / proxy / hosting network ({$log->isp}).";
        }

        if ($unit) {
            $batch   = $unit->batch;
            $product = $batch?->product;

            // Authorized markets for this product (if any are configured).
            $authorized = $product
                ? CountryAuthorization::where('product_id', $product->id)->pluck('country_code')->map(fn ($c) => strtoupper($c))->all()
                : [];

            // 9.3 — Unauthorized market.
            if ($authorized && $log->country_code && !in_array(strtoupper($log->country_code), $authorized, true)) {
                $hits['unauthorized_market'] = "{$log->country} is not an authorized market for this product.";
            }

            // 9.13 — First scan country mismatch.
            if ($authorized && $history->isEmpty() && $log->country_code && !in_array(strtoupper($log->country_code), $authorized, true)) {
                $hits['first_scan_mismatch'] = "First verification occurred outside authorized markets ({$log->country}).";
            }

            // 9.5 — Expired product scan.
            if ($batch?->expiry_date && $batch->expiry_date->isPast()) {
                $hits['expired_scan'] = "Expired product (expired {$batch->expiry_date->format('d M Y')}).";
            }

            // 9.6 — Recalled batch scan (country-aware).
            if (ProductRecall::activeForUnit($unit, $log->country_code) || in_array($batch?->status, ['recalled'], true) || in_array($batch?->qc_status, ['recalled'], true)) {
                $hits['recalled_scan'] = "Unit belongs to a recalled batch.";
            }

            // 9.14 — Destroyed / rejected / returned product.
            if (in_array($unit->status, ['destroyed', 'rejected', 'returned'], true) || in_array($batch?->qc_status, ['rejected'], true)) {
                $hits['destroyed_rejected'] = "Unit/batch is marked {$unit->status}/{$batch?->qc_status}.";
            }

            // 9.4 — Batch cluster: >500 scans of this batch from one country in 1h.
            if ($batch && $log->country_code) {
                $cluster = VerificationLog::where('batch_id', $batch->id)
                    ->where('country_code', $log->country_code)
                    ->where('created_at', '>=', $now->copy()->subHour())
                    ->count();
                if ($cluster > 500) {
                    $hits['batch_cluster'] = "{$cluster} scans of this batch from {$log->country} within 1 hour.";
                }
            }
        }

        // ── Access-control policy (per code / batch / product / global) ──
        $whitelisted = false;
        if ($policy) {
            $ip          = $log->ip_address;
            $whitelisted = $this->ipMatches($ip, (array) $policy->ip_whitelist);

            // IP blacklist — hard block (unless the IP is also whitelisted).
            if (!$whitelisted && $this->ipMatches($ip, (array) $policy->ip_blacklist)) {
                $hits['ip_blocked'] = "IP {$ip} is blacklisted by policy '{$policy->name}'.";
            }

            // VPN / proxy block — country-aware exemption list.
            if ($policy->block_vpn && $log->is_proxy && !$whitelisted) {
                $vpnOk = array_map('strtoupper', (array) $policy->vpn_allowed_countries);
                $countryExempt = $log->country_code && in_array(strtoupper($log->country_code), $vpnOk, true);
                if (!$countryExempt) {
                    $hits['vpn_blocked'] = "VPN/proxy detected ({$log->isp}) — verification over VPN is not allowed"
                        . ($log->country ? " in {$log->country}." : ".");
                }
            }

            if ($policy->locked) {
                $hits['locked_scope'] = "Verification is locked for this {$policy->scope_label} by policy '{$policy->name}'.";
            }

            $allowedCountries = array_map('strtoupper', (array) $policy->allowed_countries);
            if ($allowedCountries && $log->country_code && !in_array(strtoupper($log->country_code), $allowedCountries, true)) {
                $hits['unauthorized_market'] = "{$log->country} is not permitted by policy '{$policy->name}'.";
            }

            $allowedCities = array_map('mb_strtolower', (array) $policy->allowed_cities);
            if ($allowedCities && $log->city && !in_array(mb_strtolower($log->city), $allowedCities, true)) {
                $hits['unauthorized_city'] = "{$log->city} is not a permitted city by policy '{$policy->name}'.";
            }

            if ($policy->scan_limit) {
                $scanCount = $this->scopeQuery($policy, $log)->count();
                if ($scanCount > $policy->scan_limit) {
                    $hits['scan_limit_exceeded'] = "{$scanCount} scans exceed the limit of {$policy->scan_limit} for this {$policy->scope_label}.";
                }
            }

            if ($policy->device_limit) {
                $deviceCount = $this->scopeQuery($policy, $log)->whereNotNull('user_agent')->distinct()->count('user_agent');
                if ($deviceCount > $policy->device_limit) {
                    $hits['device_limit_exceeded'] = "{$deviceCount} devices exceed the limit of {$policy->device_limit} for this {$policy->scope_label}.";
                }
            }
        }

        // ── Trusted-IP / "Allow all" policy override ──
        // A whitelisted IP, or a policy explicitly set to allow-all, bypasses
        // every access-control + IP/VPN block. Integrity rules (recall, expiry,
        // destroyed/rejected) and fraud signals are intentionally left in place.
        if ($policy && ($whitelisted || $policy->allow_all)) {
            unset(
                $hits['locked_scope'],
                $hits['unauthorized_market'],
                $hits['unauthorized_city'],
                $hits['scan_limit_exceeded'],
                $hits['device_limit_exceeded'],
                $hits['ip_blocked'],
                $hits['vpn_blocked'],
                $hits['first_scan_mismatch'],
            );
        }

        // ── "Verify anywhere" product override ──
        // An explicitly-opened product bypasses ALL access-control + geographic
        // locks (policy lock, allowed-countries/cities, and Country
        // Authorization). Integrity rules — recall, expiry, destroyed/rejected,
        // and fraud signals — are intentionally left in place.
        if ($unit?->batch?->product?->verify_open) {
            unset(
                $hits['locked_scope'],
                $hits['unauthorized_market'],
                $hits['unauthorized_city'],
                $hits['ip_blocked'],
                $hits['vpn_blocked'],
                $hits['first_scan_mismatch'],
            );
        }

        // ── Persist alerts + tally score ──
        $score = 0;
        foreach ($hits as $category => $description) {
            $score += self::SCORES[$category] ?? 0;
            RiskAlert::create([
                'alert_number'        => RiskAlert::nextNumber(),
                'verification_log_id' => $log->id,
                'batch_unit_id'       => $log->batch_unit_id,
                'batch_id'            => $log->batch_id,
                'product_id'          => $log->product_id,
                'uuc_code'            => $uuc,
                'category'            => $category,
                'risk_level'          => self::LEVELS[$category] ?? 'low',
                'risk_score'          => self::SCORES[$category] ?? 0,
                'country'             => $log->country,
                'latitude'            => $log->latitude,
                'longitude'           => $log->longitude,
                'description'         => $description,
            ]);
        }

        return min(100, $score);
    }

    /**
     * True if $ip matches any entry in $list. Entries may be exact IPs
     * (e.g. 203.0.113.4) or CIDR ranges (e.g. 203.0.113.0/24). IPv4 only for
     * CIDR; exact match works for IPv4 + IPv6.
     */
    private function ipMatches(?string $ip, array $list): bool
    {
        if (!$ip || !$list) {
            return false;
        }
        foreach ($list as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') {
                continue;
            }
            if (!str_contains($entry, '/')) {
                if (strcasecmp($entry, $ip) === 0) {
                    return true;
                }
                continue;
            }
            [$subnet, $bits] = explode('/', $entry, 2);
            $ipLong = ip2long($ip);
            $subLong = ip2long($subnet);
            $bits = (int) $bits;
            if ($ipLong === false || $subLong === false || $bits < 0 || $bits > 32) {
                continue;
            }
            $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));
            if (($ipLong & $mask) === ($subLong & $mask)) {
                return true;
            }
        }
        return false;
    }

    /** Base query over verification logs for a policy's scope. */
    private function scopeQuery(VerificationPolicy $policy, VerificationLog $log)
    {
        $q = VerificationLog::query();
        return match ($policy->scope_type) {
            'codes'   => $q->whereIn('uuc_code', (array) $policy->uuc_codes),
            'batch'   => $q->where('batch_id', $log->batch_id),
            'product' => $q->where('product_id', $log->product_id),
            default   => $q->where('uuc_code', $log->uuc_code),
        };
    }

    /** Great-circle distance between two lat/lng points, in kilometres. */
    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371; // Earth radius (km)
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
