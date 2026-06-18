<?php

namespace App\Services;

/**
 * Lightweight user-agent parser — no external package. Good enough to label
 * browser, OS and device type for verification intelligence.
 */
class DeviceParser
{
    public function parse(?string $ua): array
    {
        $ua = (string) $ua;

        return [
            'browser'     => $this->browser($ua),
            'os'          => $this->os($ua),
            'device_type' => $this->deviceType($ua),
        ];
    }

    private function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg')                                   => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera')     => 'Opera',
            str_contains($ua, 'SamsungBrowser')                        => 'Samsung Internet',
            str_contains($ua, 'Firefox')                               => 'Firefox',
            str_contains($ua, 'Chrome')                                => 'Chrome',
            str_contains($ua, 'Safari')                                => 'Safari',
            str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')  => 'Internet Explorer',
            default                                                     => 'Unknown',
        };
    }

    private function os(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows NT 10') => 'Windows 10/11',
            str_contains($ua, 'Windows')       => 'Windows',
            str_contains($ua, 'Android')       => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS X')      => 'macOS',
            str_contains($ua, 'Linux')         => 'Linux',
            default                             => 'Unknown',
        };
    }

    private function deviceType(string $ua): string
    {
        if (str_contains($ua, 'iPad') || str_contains($ua, 'Tablet')) {
            return 'tablet';
        }
        if (str_contains($ua, 'Mobi') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone')) {
            return 'mobile';
        }
        return 'desktop';
    }
}
