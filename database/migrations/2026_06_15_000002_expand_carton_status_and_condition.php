<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 2 — richer carton lifecycle:
     *  - widen `status` from the 4-value enum to a flexible string so it can hold
     *    quality_checked / ready_for_dispatch / in_transit / received_ho /
     *    received_depot / damaged / returned / closed, etc.
     *  - track physical condition + evidence for damage / shortage handling.
     *
     * `carton_condition` (not `condition`, which is a MySQL reserved word).
     */
    public function up(): void
    {
        // Widen status without requiring doctrine/dbal.
        DB::statement("ALTER TABLE master_cartons MODIFY status VARCHAR(40) NOT NULL DEFAULT 'created'");

        Schema::table('master_cartons', function (Blueprint $table) {
            $table->string('carton_condition', 20)->default('good')->after('status'); // good / damaged / missing / returned
            $table->text('condition_note')->nullable()->after('carton_condition');
            $table->string('evidence_path')->nullable()->after('condition_note');
            $table->string('received_location')->nullable()->after('evidence_path');

            $table->index('carton_condition');
        });
    }

    public function down(): void
    {
        Schema::table('master_cartons', function (Blueprint $table) {
            $table->dropIndex(['carton_condition']);
            $table->dropColumn(['carton_condition', 'condition_note', 'evidence_path', 'received_location']);
        });

        DB::statement("ALTER TABLE master_cartons MODIFY status ENUM('created','packed','dispatched','received') NOT NULL DEFAULT 'created'");
    }
};
