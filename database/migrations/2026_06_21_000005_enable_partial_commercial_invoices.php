<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enable partial Commercial Invoices: one Proforma Invoice may be shipped /
 * invoiced across many CIs. Drops the 1-PI-1-CI unique constraint and links
 * each CI line back to the PI line it draws from, so remaining quantity can be
 * tracked per line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table) {
            $table->dropForeign(['proforma_invoice_id']);
            $table->dropUnique('commercial_invoices_proforma_invoice_id_unique');
            $table->foreign('proforma_invoice_id')->references('id')->on('proforma_invoices')->restrictOnDelete();
            $table->index('proforma_invoice_id');
        });

        Schema::table('commercial_invoice_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('commercial_invoice_lines', 'proforma_invoice_line_id')) {
                $table->unsignedBigInteger('proforma_invoice_line_id')->nullable()->after('commercial_invoice_id');
                $table->foreign('proforma_invoice_line_id')->references('id')->on('proforma_invoice_lines')->nullOnDelete();
                $table->index('proforma_invoice_line_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('commercial_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['proforma_invoice_line_id']);
            $table->dropColumn('proforma_invoice_line_id');
        });
        Schema::table('commercial_invoices', function (Blueprint $table) {
            $table->dropForeign(['proforma_invoice_id']);
            $table->dropIndex(['proforma_invoice_id']);
            $table->unique('proforma_invoice_id');
            $table->foreign('proforma_invoice_id')->references('id')->on('proforma_invoices')->restrictOnDelete();
        });
    }
};
