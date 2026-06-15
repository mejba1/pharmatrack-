<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scan & movement history for master cartons (QR scans plus Factory dispatch
     * and Depot receiving events).
     */
    public function up(): void
    {
        Schema::create('master_carton_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_carton_id')->constrained()->cascadeOnDelete();
            $table->enum('event', ['scanned', 'dispatched', 'received'])->default('scanned');
            $table->string('performed_by')->nullable();
            $table->string('location')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('master_carton_id');
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_carton_scans');
    }
};
