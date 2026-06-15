<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Move master cartons to a GS1-style aggregation model so a carton can be:
     *  - generic (created with no product/batch, assigned at packing), and
     *  - mixed (hold multiple products / batches / serial ranges).
     *
     * Each packed segment lives in master_carton_contents; the carton's own
     * product_id/batch_id/serial_start/serial_end remain only as a convenience
     * summary for the common homogeneous case (NULL when generic or mixed).
     */
    public function up(): void
    {
        Schema::table('master_cartons', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unsignedBigInteger('batch_id')->nullable()->change();
            $table->enum('carton_type', ['standard', 'generic'])->default('standard')->after('qr_code');
            $table->string('label')->nullable()->after('carton_type');   // optional free label for generic cartons
        });

        Schema::create('master_carton_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_carton_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->string('partial_batch_ref')->nullable();
            $table->unsignedInteger('serial_start');
            $table->unsignedInteger('serial_end');
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->index('master_carton_id');
            $table->index('batch_id');
        });

        // Backfill: turn any already-packed carton into a single content row.
        $packed = DB::table('master_cartons')
            ->whereNotNull('serial_start')
            ->whereNotNull('batch_id')
            ->where('packed_quantity', '>', 0)
            ->get();

        foreach ($packed as $c) {
            DB::table('master_carton_contents')->insert([
                'master_carton_id' => $c->id,
                'product_id'       => $c->product_id,
                'batch_id'         => $c->batch_id,
                'serial_start'     => $c->serial_start,
                'serial_end'       => $c->serial_end,
                'quantity'         => $c->packed_quantity,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_carton_contents');

        Schema::table('master_cartons', function (Blueprint $table) {
            $table->dropColumn(['carton_type', 'label']);
            // Note: leaving product_id/batch_id nullable on rollback is harmless;
            // re-tightening would fail if generic cartons exist.
        });
    }
};
