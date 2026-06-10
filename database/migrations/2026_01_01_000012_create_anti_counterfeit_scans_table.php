<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anti-Counterfeit Scans  (UUC — Unique Unit Code scan log)
     *
     * Each scannable unit (box, vial, blister strip) carries a QR / datamatrix
     * UUC that is scanned at various points in the supply chain.
     * Results are stored here and used in the Anti-Counterfeit module.
     */
    public function up(): void
    {
        Schema::create('anti_counterfeit_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('uuc')->unique();                    // the unique unit code printed on the pack
            $table->string('serial_number')->nullable();
            $table->string('aggregation_code')->nullable();     // outer carton / pallet code
            $table->boolean('is_active')->default(true);
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('batch_id');
        });

        Schema::create('anti_counterfeit_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_id')
                  ->constrained('anti_counterfeit_codes')
                  ->restrictOnDelete();

            // Who / where
            $table->foreignId('scanned_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->foreignId('distributor_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->string('scan_location')->nullable();        // free-text: city / facility name
            $table->string('country_code', 5)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('device_id')->nullable();            // scanner device identifier
            $table->string('ip_address', 45)->nullable();       // IPv4 or IPv6

            // Result
            $table->enum('result', [
                'authentic',
                'counterfeit',
                'duplicate_scan',
                'expired',
                'unknown_code',
                'error',
            ])->default('authentic');

            $table->unsignedSmallInteger('scan_count_at_time')->default(1); // how many times this UUC had been scanned before this event
            $table->text('notes')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index('code_id');
            $table->index('result');
            $table->index('scanned_at');
            $table->index(['country_code', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anti_counterfeit_scans');
        Schema::dropIfExists('anti_counterfeit_codes');
    }
};
