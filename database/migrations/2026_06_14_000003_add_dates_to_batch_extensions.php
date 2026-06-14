<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Effective manufacturing / expiry dates for a partial batch. These may
     * differ from the parent batch (a later production run can have its own
     * mfg & expiry), and default to the parent batch's dates when not overridden.
     */
    public function up(): void
    {
        Schema::table('batch_extensions', function (Blueprint $table) {
            $table->date('manufacture_date')->nullable()->after('serial_end');
            $table->date('expiry_date')->nullable()->after('manufacture_date');
        });
    }

    public function down(): void
    {
        Schema::table('batch_extensions', function (Blueprint $table) {
            $table->dropColumn(['manufacture_date', 'expiry_date']);
        });
    }
};
