<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Carton Management — groups finished units into shipping cartons for
     * internal Factory→Depot distribution. Each carton has a unique number and a
     * QR code, and (once packed) a contiguous serial range from its batch.
     */
    public function up(): void
    {
        Schema::create('master_cartons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();

            $table->string('carton_number')->unique();      // e.g. MC-000001
            $table->string('qr_code')->unique();            // scan token (QR payload)
            $table->unsignedInteger('capacity');            // products per carton
            $table->unsignedInteger('packed_quantity')->default(0);
            $table->unsignedInteger('serial_start')->nullable();
            $table->unsignedInteger('serial_end')->nullable();

            $table->enum('status', ['created', 'packed', 'dispatched', 'received'])->default('created');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('batch_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('qr_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_cartons');
    }
};
