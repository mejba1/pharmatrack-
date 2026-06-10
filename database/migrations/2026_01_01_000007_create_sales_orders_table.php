<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales Orders  (SO-2026-XXXX)
     * Raised by the manufacturer in response to an acknowledged PO.
     * One PO → one SO (1:1 in this system).
     */
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number')->unique();               // e.g. SO-2026-0241

            // Upstream link
            $table->foreignId('purchase_order_id')
                  ->unique()                                    // 1 PO → 1 SO
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Customer (mirrors PO buyer)
            $table->foreignId('customer_id')->constrained('distributors')->restrictOnDelete();
            $table->foreignId('ship_to_country_id')->nullable()->constrained('countries')->nullOnDelete();

            // Dates
            $table->date('so_date');
            $table->date('estimated_delivery_date')->nullable();

            // Commercial terms
            $table->string('currency', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            $table->string('incoterms', 10)->nullable();
            $table->string('port_of_loading')->nullable();
            $table->string('port_of_discharge')->nullable();

            // Financials
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('freight', 16, 2)->default(0);
            $table->decimal('total_value', 16, 2)->default(0);

            $table->enum('status', [
                'draft',
                'confirmed',
                'pi_issued',
                'completed',
                'cancelled',
            ])->default('draft');

            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('status');
            $table->index('so_date');
        });

        // ── SO Line Items ─────────────────────────────────────────
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();

            $table->integer('line_number');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('line_total', 16, 2);
            $table->string('unit_of_measure', 20)->default('unit');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
