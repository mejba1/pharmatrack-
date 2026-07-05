<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IP & VPN/proxy access controls on verification policies, plus per-unit VPN
 * scan tracking.
 *
 * Policy fields:
 *   • allow_all             — bypass all access-control blocks (master allow)
 *   • ip_whitelist          — IPs/CIDRs that always pass (trusted, bypass blocks)
 *   • ip_blacklist          — IPs/CIDRs that are always blocked
 *   • block_vpn             — block verification when a VPN/proxy is detected
 *   • vpn_allowed_countries — country codes exempt from the VPN block
 *
 * Unit fields:
 *   • vpn_scan_count        — how many scans of this UUC came over a VPN/proxy
 *   • last_vpn_scan_at      — timestamp of the most recent VPN/proxy scan
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_policies', function (Blueprint $table) {
            $table->boolean('allow_all')->default(false)->after('locked');
            $table->json('ip_whitelist')->nullable()->after('allowed_cities');
            $table->json('ip_blacklist')->nullable()->after('ip_whitelist');
            $table->boolean('block_vpn')->default(false)->after('ip_blacklist');
            $table->json('vpn_allowed_countries')->nullable()->after('block_vpn');
        });

        Schema::table('batch_units', function (Blueprint $table) {
            $table->unsignedInteger('vpn_scan_count')->default(0)->after('last_blocked_scan_at');
            $table->timestamp('last_vpn_scan_at')->nullable()->after('vpn_scan_count');
        });
    }

    public function down(): void
    {
        Schema::table('verification_policies', function (Blueprint $table) {
            $table->dropColumn(['allow_all', 'ip_whitelist', 'ip_blacklist', 'block_vpn', 'vpn_allowed_countries']);
        });
        Schema::table('batch_units', function (Blueprint $table) {
            $table->dropColumn(['vpn_scan_count', 'last_vpn_scan_at']);
        });
    }
};
