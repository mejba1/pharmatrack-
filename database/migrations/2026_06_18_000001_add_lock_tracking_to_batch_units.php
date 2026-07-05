<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lock forensics on each unit (UUC):
 *   • lock_reason          — why this specific UUC was locked (free text)
 *   • locked_at            — when it was locked
 *   • blocked_scan_count   — how many scans were blocked AFTER lock / hit-limit
 *                            cross (for future identification of abuse)
 *   • last_blocked_scan_at — timestamp of the most recent blocked scan
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_units', function (Blueprint $table) {
            $table->string('lock_reason', 500)->nullable()->after('status');
            $table->timestamp('locked_at')->nullable()->after('lock_reason');
            $table->unsignedInteger('blocked_scan_count')->default(0)->after('locked_at');
            $table->timestamp('last_blocked_scan_at')->nullable()->after('blocked_scan_count');
        });
    }

    public function down(): void
    {
        Schema::table('batch_units', function (Blueprint $table) {
            $table->dropColumn(['lock_reason', 'locked_at', 'blocked_scan_count', 'last_blocked_scan_at']);
        });
    }
};
