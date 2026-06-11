<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trace / audit log for batch-unit lifecycle events
     * (generation, top-up, removal, status changes, scans, verification…).
     */
    public function up(): void
    {
        Schema::create('batch_unit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');                 // units_generated, units_removed, status_changed, scanned…
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->integer('quantity')->nullable(); // for bulk events
            $table->string('note')->nullable();
            $table->string('performed_by')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_unit_logs');
    }
};
