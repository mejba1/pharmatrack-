<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One serialized unit per item in a batch's produced quantity.
     * Used for packaging labels and product-verification (anti-counterfeit).
     */
    public function up(): void
    {
        Schema::create('batch_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('serial_number');            // 1..quantity within the batch
            $table->string('secret_code', 16);                   // 10-digit verification PIN (QR payload)
            $table->string('unique_number')->unique();           // packaging-label identifier
            $table->enum('status', [
                'generated', 'printing', 'packed', 'scanned',
                'blocked', 'active', 'inactive', 'verified', 'expired',
            ])->default('generated');
            $table->timestamps();

            $table->unique(['batch_id', 'serial_number']);
            $table->index('secret_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_units');
    }
};
