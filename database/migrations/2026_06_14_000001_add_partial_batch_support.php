<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partial Batch Quantity (Batch Quantity Extension) support.
     *  - batches.quantity_extended   — running total of units added after creation.
     *  - batch_units.partial_batch_ref — tags which extension a unit came from
     *    (NULL = part of the original batch creation).
     *
     * The serial-number uniqueness is widened to include partial_batch_ref so an
     * extension can legitimately "restart from 1" without colliding with the
     * original units (which keep partial_batch_ref = NULL).
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->integer('quantity_extended')->default(0)->after('quantity_available');
        });

        Schema::table('batch_units', function (Blueprint $table) {
            $table->string('partial_batch_ref')->nullable()->after('batch_id');
            $table->index('partial_batch_ref');
        });

        // Add the wider unique index BEFORE dropping the old one — the foreign key
        // on batch_id needs an index with batch_id as its leftmost column at all
        // times, and the new composite unique satisfies that requirement.
        Schema::table('batch_units', function (Blueprint $table) {
            $table->unique(['batch_id', 'partial_batch_ref', 'serial_number'], 'batch_units_batch_partial_serial_unique');
        });
        Schema::table('batch_units', function (Blueprint $table) {
            $table->dropUnique(['batch_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::table('batch_units', function (Blueprint $table) {
            $table->dropUnique('batch_units_batch_partial_serial_unique');
            $table->dropIndex(['partial_batch_ref']);
            $table->dropColumn('partial_batch_ref');
            $table->unique(['batch_id', 'serial_number']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('quantity_extended');
        });
    }
};
