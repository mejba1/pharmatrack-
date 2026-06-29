<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link specific units to the Sales Order that allocated them (serial
 * range/specific allocation at SO creation). `sold_to_id` / `sold_at` (added
 * for the customer-sale module) are reused so the Trace Purchase tool also
 * finds SO-allocated units.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_units', function (Blueprint $table) {
            if (!Schema::hasColumn('batch_units', 'sales_order_id')) {
                $table->unsignedBigInteger('sales_order_id')->nullable()->after('customer_sale_id');
                $table->foreign('sales_order_id')->references('id')->on('sales_orders')->nullOnDelete();
                $table->index('sales_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('batch_units', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn('sales_order_id');
        });
    }
};
