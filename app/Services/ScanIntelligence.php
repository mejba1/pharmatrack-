<?php

namespace App\Services;

use App\Models\BatchUnit;
use App\Models\CountryAuthorization;
use App\Models\VerificationLog;
use Illuminate\Support\Collection;

/**
 * Evaluates a freshly-created VerificationLog against the admin-configurable
 * "scan intelligence" rules (8 scenarios) and produces:
 *   • alerts      — customer-facing warnings/notices with previous-scan records
 *   • block       — optional hard block reason ('locked' | 'rate') for the page
 *   • lock        — optional payload telling the caller to permanently lock the UUC
 *   • certificate — whether the returning-scan certificate prompt should show
 *   • intendedRegion — region/country to surface on a region mismatch
 *
 * All thresholds, messages, record counts and report-form toggles come from
 * $cfg['scan_intelligence'] (see Setting::scanIntelligenceDefaults()).
 */
class ScanIntelligence
{
    public function evaluate(VerificationLog $log, ?BatchUnit $unit, array $cfg): array
    {
        $out = ['alerts' => [], 'block' => null, 'lock' => null, 'certificate' => false, 'intendedRegion' => null];
        if (!$unit) {
            return $out;
        }

        $rules = $cfg['scan_intelligence'] ?? [];
        $code  = $log->uuc_code;
        $now   = $log->created_at ?? now();
        $ip    = $log->ip_address;

        // History of this UUC excluding the current scan (newest first) + the full set.
        $history = VerificationLog::where('uuc_code', $code)
            ->where('id', '!=', $log->id)
            ->latest()->limit(500)->get();
        $all = $history->concat([$log]);

        $on   = fn (string $k) => !empty($rules[$k]['enabled']);
        $r    = fn (string $k) => $rules[$k] ?? [];
        $int  = fn ($v, $d) => (int) ($v ?? $d);

        $distinctCountries = $all->pluck('country_code')->filter()->unique();
        $distinctIps       = $all->pluck('ip_address')->filter()->unique();
        $sameIp            = $ip ? $all->filter(fn ($h) => $h->ip_address === $ip) : collect();
        $sameIpHistory     = $sameIp->reject(fn ($h) => $h->id === $log->id);

        // ══ PHASE 1 — Hard blocks (mutually exclusive, most severe wins) ══
        // A blocked scan shows only the blocking alert; soft warnings are
        // suppressed to keep the message clear and unambiguous.

        // 4 — Same IP repeated past the lock threshold → permanent auto-lock.
        if ($on('same_ip_autolock') && $ip && $sameIp->count() >= $int($r('same_ip_autolock')['threshold'] ?? null, 8)) {
            $out['block'] = 'locked';
            $out['lock']  = ['reason' => 'Auto-locked: ' . $sameIp->count() . ' repeated scans from ' . $ip . '.'];
            $out['alerts'][] = $this->alert($r('same_ip_autolock'), 'same_ip_autolock', 'danger',
                $this->records($sameIpHistory, $int($r('same_ip_autolock')['records'] ?? null, 5), ['ip', 'city', 'time']));
            return $out;
        }

        // 2 — Same IP repeated past the block threshold → soft block + contact admin.
        if ($on('repeat_ip_block') && $ip && $sameIp->count() >= $int($r('repeat_ip_block')['threshold'] ?? null, 5)) {
            $out['block'] = 'rate';
            $out['alerts'][] = $this->alert($r('repeat_ip_block'), 'repeat_ip_block', 'danger',
                $this->records($sameIpHistory, $int($r('repeat_ip_block')['records'] ?? null, 5), ['ip', 'city', 'time']));
            return $out;
        }

        // 6 — High-frequency scans in a short window → temporary block.
        if ($on('high_freq')) {
            $win = $int($r('high_freq')['window_min'] ?? null, 10);
            $max = $int($r('high_freq')['max'] ?? null, 3);
            $recent = $all->filter(fn ($h) => $h->created_at && $h->created_at->gt($now->copy()->subMinutes($win)))->count();
            if ($recent > $max) {
                $out['block'] = 'rate';
                $out['alerts'][] = $this->alert($r('high_freq'), 'high_freq', 'danger', []);
                return $out;
            }
        }

        // ══ PHASE 2 — Soft warnings (genuine scans only; these stack) ══

        // 7 — Region / location mismatch (scan country not an authorized market).
        if ($on('region_mismatch') && $log->country_code) {
            $product    = $unit->batch?->product;
            $authorized = $product
                ? CountryAuthorization::where('product_id', $product->id)->pluck('country_code')->map(fn ($c) => strtoupper($c))->all()
                : [];
            if ($authorized && !in_array(strtoupper($log->country_code), $authorized, true)) {
                $intended = $product?->country_of_origin ?: implode(', ', $authorized);
                $out['intendedRegion'] = $intended;
                $alert = $this->alert($r('region_mismatch'), 'region_mismatch', 'warning', []);
                $alert['intended'] = $intended;
                $out['alerts'][] = $alert;
            }
        }

        // 3 — Multiple IPs across different countries (richer; supersedes plain
        //     multi-country, so they don't both fire for the same situation).
        $multiIpDiff = $on('multi_ip_diff_country')
            && $distinctIps->count() >= $int($r('multi_ip_diff_country')['threshold'] ?? null, 3)
            && $distinctCountries->count() >= 2;
        if ($multiIpDiff) {
            $out['alerts'][] = $this->alert($r('multi_ip_diff_country'), 'multi_ip_diff_country', 'warning',
                $this->records($history, $int($r('multi_ip_diff_country')['records'] ?? null, 3), ['ip', 'country', 'city', 'time']));
        }

        // 1 — Multiple country detections (only if the richer rule 3 didn't fire).
        if (!$multiIpDiff && $on('multi_country') && $distinctCountries->count() >= $int($r('multi_country')['threshold'] ?? null, 2)) {
            $out['alerts'][] = $this->alert($r('multi_country'), 'multi_country', 'warning',
                $this->records($history, $int($r('multi_country')['records'] ?? null, 3), ['country', 'city', 'time']));
        }

        // 3a — Multiple IPs within the same country.
        if ($on('multi_ip_same_country')
            && $distinctIps->count() >= $int($r('multi_ip_same_country')['threshold'] ?? null, 3)
            && $distinctCountries->count() <= 1) {
            $out['alerts'][] = $this->alert($r('multi_ip_same_country'), 'multi_ip_same_country', 'warning',
                $this->records($history, $int($r('multi_ip_same_country')['records'] ?? null, 3), ['ip', 'city', 'time']));
        }

        // 5 — Already scanned within N days from the same IP (informational).
        if ($on('recent_3day') && $ip) {
            $days = $int($r('recent_3day')['days'] ?? null, 3);
            $recentSameIp = $sameIpHistory->filter(fn ($h) => $h->created_at && $h->created_at->gt($now->copy()->subDays($days)));
            if ($recentSameIp->isNotEmpty()) {
                $out['alerts'][] = $this->alert($r('recent_3day'), 'recent_3day', 'warning',
                    $this->records($recentSameIp, $int($r('recent_3day')['records'] ?? null, 5), ['ip', 'city', 'time']));
            }
        }

        // 8 — Returning scan: same IP + device after N+ days → certificate prompt.
        if ($on('returning_scan') && $ip) {
            $days = $int($r('returning_scan')['days'] ?? null, 10);
            $prev = $sameIpHistory->first(fn ($h) => $this->sameDevice($h, $log));
            if ($prev && $prev->created_at && $prev->created_at->lte($now->copy()->subDays($days))) {
                $out['certificate'] = true;
                $alert = $this->alert($r('returning_scan'), 'returning_scan', 'info',
                    $this->records(collect([$prev]), $int($r('returning_scan')['records'] ?? null, 3), ['ip', 'city', 'time']));
                $alert['days_ago'] = (int) $prev->created_at->diffInDays($now);
                $alert['certificate'] = true;
                $out['alerts'][] = $alert;
            }
        }

        return $out;
    }

    private function sameDevice(VerificationLog $a, VerificationLog $b): bool
    {
        return $a->user_agent && $b->user_agent && $a->user_agent === $b->user_agent;
    }

    private function alert(array $rule, string $key, string $level, array $records): array
    {
        return [
            'key'     => $key,
            'level'   => $level,
            'title'   => $rule['title'] ?? 'Notice',
            'text'    => $rule['text'] ?? '',
            'records' => $records,
            'report'  => !empty($rule['report']),
        ];
    }

    /** Build up to $limit record rows with the requested columns. */
    private function records(Collection $logs, int $limit, array $cols): array
    {
        if ($limit <= 0) {
            return [];
        }
        return $logs->take($limit)->map(function (VerificationLog $l) use ($cols) {
            $row = [];
            foreach ($cols as $c) {
                $row[$c] = match ($c) {
                    'ip'      => $l->ip_address ?: '—',
                    'country' => $l->country ?: ($l->country_code ?: '—'),
                    'city'    => $l->city ?: '—',
                    'time'    => $l->created_at?->format('d M Y, H:i') ?? '—',
                    default   => '—',
                };
            }
            return $row;
        })->values()->all();
    }
}
