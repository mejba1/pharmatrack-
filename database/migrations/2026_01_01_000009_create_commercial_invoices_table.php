<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Commercial Invoices  (CI-2026-XXXX)
     * Raised from an approved PI; used for customs clearance.
     * Finance approval required before a Shipment can be created.
     */
    public function up(): void
    {
        Schema::create('commercial_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('ci_number')->unique();               // e.g. CI-2026-0072

            // Upstream link
            $table->foreignId('proforma_invoice_id')
                  ->unique()                                    // 1 PI → 1 CI
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Document date
            $table->date('ci_date');

            // Customs fields
            $table->string('hs_code', 20);                       // e.g. 3004.20.10
            $table->string('country_of_origin', 5)->default('US');
            $table->string('incoterms', 10)->nullable();
            $table->string('port_of_loading')->nullable();
            $table->string('port_of_discharge')->nullable();

            // Commercial terms
            $table->string('currency', 3)->default('USD');
            $table->string('payment_terms')->nullable();

            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_swift_code', 15)->nullable();

            // Financials
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('freight', 16, 2)->default(0);
            $table->decimal('insurance', 16, 2)->default(0);
            $table->decimal('total_value', 16, 2)->default(0);

            // Finance approval workflow
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'shipment_created',
                'cancelled',
            ])->default('draft');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('ci_date');
            $table->index('hs_code');
        });

        // ── CI Line Items ─────────────────────────────────────────
        Schema::create('commercial_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();

            $table->integer('line_number');
            $table->string('product_description')->nullable();   // customs description
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('line_total', 16, 2);
            $table->string('unit_of_measure', 20)->default('unit');
            $table->decimal('net_weight_kg', 10, 3)->nullable();
            $table->decimal('gross_weight_kg', 10, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('commercial_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_invoice_lines');
        Schema::dropIfExists('commercial_invoices');
    }
};
