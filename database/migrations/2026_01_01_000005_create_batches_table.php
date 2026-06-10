<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch & Lot Management
     * BRN = Batch Registration Number, e.g. BRN-00142-2601-003
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->string('brn')->unique();                        // e.g. BRN-00142-2601-003
            $table->string('batch_number');                         // manufacturer's internal batch no.
            $table->string('lot_number')->nullable();

            $table->date('manufacture_date');
            $table->date('expiry_date');
            $table->integer('quantity_produced');                   // units produced
            $table->integer('quantity_available')->default(0);      // current stock

            $table->string('manufacturing_site')->nullable();
            $table->string('manufacturing_country', 5)->nullable(); // ISO code

            // Quality Control
            $table->enum('qc_status', [
                'pending', 'released', 'quarantine', 'rejected', 'recalled',
            ])->default('pending');
            $table->string('qc_approved_by')->nullable();
            $table->date('qc_approval_date')->nullable();
            $table->string('coa_document_path')->nullable();        // Certificate of Analysis

            // Cold-chain / storage
            $table->string('storage_conditions')->nullable();
            $table->decimal('storage_temp_min', 5, 2)->nullable();  // °C
            $table->decimal('storage_temp_max', 5, 2)->nullable();  // °C

            $table->enum('status', [
                'active', 'expired', 'recalled', 'quarantine', 'depleted',
            ])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('qc_status');
            $table->index('status');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
