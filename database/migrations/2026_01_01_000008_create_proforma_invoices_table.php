<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proforma Invoices  (PI-2026-XXXX)
     * Issued against an SO; must be Finance-approved before a CI can be raised.
     */
    public function up(): void
    {
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('pi_number')->unique();               // e.g. PI-2026-0091

            // Upstream link
            $table->foreignId('sales_order_id')
                  ->unique()                                    // 1 SO → 1 PI
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Document dates
            $table->date('pi_date');
            $table->date('valid_until');

            // Commercial terms
            $table->string('currency', 3)->default('USD');
            $table->string('incoterms', 10)->nullable();
            $table->string('port_of_loading')->nullable();
            $table->string('payment_terms')->nullable();

            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_swift_code', 15)->nullable();
            $table->string('bank_iban')->nullable();

            // Financials
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('tax_amount', 16, 2)->default(0);   // 0% for export
            $table->decimal('freight', 16, 2)->default(0);
            $table->decimal('total_value', 16, 2)->default(0);

            // Finance approval workflow
            $table->enum('status', [
                'draft',
                'sent',
                'pending_approval',
                'approved',
                'rejected',
            ])->default('draft');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('pi_date');
            $table->index('valid_until');
        });

        // ── PI Line Items ─────────────────────────────────────────
        Schema::create('proforma_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();

            $table->integer('line_number');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('line_total', 16, 2);
            $table->string('unit_of_measure', 20)->default('unit');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('proforma_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_lines');
        Schema::dropIfExists('proforma_invoices');
    }
};
