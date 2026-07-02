<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-product (line) discount on commercial invoices.
        Schema::table('commercial_invoice_lines', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('line_total');
        });

        // Payments received against a commercial invoice (the billable document).
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('paid_on');
            $table->string('method', 30)->default('bank_transfer'); // bank_transfer, cash, cheque, card, other
            $table->string('reference', 120)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commercial_invoice_id', 'paid_on']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::table('commercial_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });
    }
};
