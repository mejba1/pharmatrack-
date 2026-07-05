<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One record per "Partial Batch Quantity" event — additional units generated
     * for an existing batch after its initial creation. Kept for traceability and
     * reporting (each carries its own Partial Batch Reference Number).
     */
    public function up(): void
    {
        Schema::create('batch_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->string('partial_ref')->unique();            // e.g. PBN-00142-2606-001
            $table->integer('additional_quantity');             // units generated in this extension

            $table->enum('serial_mode', ['continue', 'restart'])->default('continue');
            $table->unsignedInteger('serial_start');            // first serial in this extension
            $table->unsignedInteger('serial_end');              // last serial in this extension

            $table->string('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('batch_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_extensions');
    }
};
