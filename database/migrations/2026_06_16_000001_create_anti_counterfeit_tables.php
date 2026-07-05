<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anti-Counterfeit Product Verification & Intelligence System — core tables.
     *
     *  • verification_logs     — one row per public /verify scan (geo + device + risk)
     *  • risk_alerts           — auto-generated risk findings / investigation cases
     *  • country_authorizations— per-product authorized markets (allowed countries)
     *  • product_recalls       — batch / country / global recalls
     */
    public function up(): void
    {
        // ── Every verification scan ──────────────────────────────────────────
        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('verification_number')->unique();      // VER-YYYYMMDD-NNNNNN
            $table->string('uuc_code')->index();                  // the scanned secret code
            $table->foreignId('batch_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // genuine | suspicious | invalid | expired | recalled
            $table->string('result')->default('genuine')->index();
            $table->unsignedTinyInteger('risk_score')->default(0);

            // Network / geo intelligence
            $table->string('ip_address', 45)->nullable();
            $table->string('country')->nullable()->index();
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('isp')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('gps_accuracy')->nullable();   // metres, from browser GPS
            $table->boolean('is_proxy')->default(false);           // VPN / proxy / TOR

            // Device intelligence
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('device_type')->nullable();             // mobile / tablet / desktop
            $table->string('language')->nullable();
            $table->string('timezone')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['batch_id', 'created_at']);
            $table->index(['country_code', 'created_at']);
        });

        // ── Risk alerts / investigation cases ────────────────────────────────
        Schema::create('risk_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_number')->unique();              // ALT-YYYYMMDD-NNNNNN
            $table->foreignId('verification_log_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('uuc_code')->nullable()->index();

            $table->string('category');                            // geographic_impossibility, excessive_scan_rate…
            $table->string('risk_level')->index();                 // low | medium | high | critical
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable();

            // Investigation Center
            $table->string('status')->default('open')->index();    // open | investigating | resolved | dismissed
            $table->boolean('is_case')->default(false)->index();   // escalated to a counterfeit case
            $table->string('assigned_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['risk_level', 'status']);
            $table->index(['category', 'created_at']);
        });

        // ── Authorized markets per product ───────────────────────────────────
        Schema::create('country_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->string('country_name');
            $table->timestamps();

            $table->unique(['product_id', 'country_code']);
        });

        // ── Recalls ──────────────────────────────────────────────────────────
        Schema::create('product_recalls', function (Blueprint $table) {
            $table->id();
            $table->string('recall_number')->unique();             // RCL-YYYYMMDD-NNN
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope')->default('batch');             // batch | country | global
            $table->string('country_code', 2)->nullable();         // for country-scoped recalls
            $table->string('severity')->default('normal');         // normal | emergency
            $table->text('reason')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->string('recalled_by')->nullable();
            $table->timestamp('recalled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recalls');
        Schema::dropIfExists('country_authorizations');
        Schema::dropIfExists('risk_alerts');
        Schema::dropIfExists('verification_logs');
    }
};
