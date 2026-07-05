<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Geo-location / network intelligence via the free ip-api.com service.
 * Results are cached per-IP for an hour. Degrades gracefully (returns an
 * empty shape) when the service is unreachable or the IP is local.
 */
class GeoIpService
{
    public function lookup(?string $ip): array
    {
        $empty = [
            'country' => null, 'country_code' => null, 'city' => null,
            'region' => null, 'isp' => null, 'lat' => null, 'lon' => null,
            'is_proxy' => false,
        ];

        if (!$ip || $this->isPrivate($ip)) {
            return $empty;
        }

        return Cache::remember("geoip:$ip", 3600, fn () => $this->fetch($ip, $empty));
    }

    /** Query ip-api.com for an IP. */
    private function fetch(string $ip, array $empty): array
    {
        try {
            $res = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,countryCode,regionName,city,isp,lat,lon,proxy,hosting',
            ]);
            $d = $res->json();
            if (!is_array($d) || ($d['status'] ?? '') !== 'success') {
                return $empty;
            }
            return [
                'country'      => $d['country']     ?? null,
                'country_code' => $d['countryCode'] ?? null,
                'city'         => $d['city']        ?? null,
                'region'       => $d['regionName']  ?? null,
                'isp'          => $d['isp']         ?? null,
                'lat'          => $d['lat']         ?? null,
                'lon'          => $d['lon']         ?? null,
                'is_proxy'     => (bool) (($d['proxy'] ?? false) || ($d['hosting'] ?? false)),
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    private function isPrivate(string $ip): bool
    {
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
