<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase Orders  (PO-2026-XXXX)
     * Raised by a distributor/buyer against the manufacturer.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();               // e.g. PO-2026-0318

            // Parties
            $table->foreignId('buyer_id')->constrained('distributors')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Dates
            $table->date('po_date');
            $table->date('required_by_date');

            // Commercial terms
            $table->string('currency', 3)->default('USD');
            $table->string('payment_terms');                     // e.g. "30 days net", "LC"
            $table->string('incoterms', 10)->nullable();         // FOB, CIF …
            $table->string('port_of_loading')->nullable();
            $table->string('port_of_discharge')->nullable();

            // Financials
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('freight', 16, 2)->default(0);
            $table->decimal('total_value', 16, 2)->default(0);

            $table->enum('status', [
                'draft',
                'sent',
                'acknowledged',
                'cancelled',
            ])->default('draft');

            $table->date('acknowledged_date')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('buyer_id');
            $table->index('status');
            $table->index('po_date');
        });

        // ── PO Line Items ─────────────────────────────────────────
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->integer('line_number');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('line_total', 16, 2);
            $table->string('unit_of_measure', 20)->default('unit');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
