<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite indexes for million-row access patterns: keyset pagination
     * (status/created_at + id), receiving reconciliation, and serial-range
     * traceability lookups.
     */
    public function up(): void
    {
        Schema::table('master_cartons', function (Blueprint $table) {
            $table->index(['status', 'id'], 'mc_status_id_idx');
            $table->index(['consignment_id', 'carton_condition', 'received_at'], 'mc_recon_idx');
            $table->index(['created_at', 'id'], 'mc_created_id_idx');
        });

        Schema::table('master_carton_contents', function (Blueprint $table) {
            $table->index(['batch_id', 'serial_start', 'serial_end'], 'mcc_serial_idx');
        });

        Schema::table('consignments', function (Blueprint $table) {
            $table->index(['status', 'id'], 'cons_status_id_idx');
            $table->index(['created_at', 'id'], 'cons_created_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('master_cartons', function (Blueprint $table) {
            $table->dropIndex('mc_status_id_idx');
            $table->dropIndex('mc_recon_idx');
            $table->dropIndex('mc_created_id_idx');
        });
        Schema::table('master_carton_contents', function (Blueprint $table) {
            $table->dropIndex('mcc_serial_idx');
        });
        Schema::table('consignments', function (Blueprint $table) {
            $table->dropIndex('cons_status_id_idx');
            $table->dropIndex('cons_created_id_idx');
        });
    }
};
