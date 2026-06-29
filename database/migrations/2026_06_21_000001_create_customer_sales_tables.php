<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-wise sales with unit-level traceability.
 *
 * Customers reuse the existing `distributors` table (extended with a
 * `customer_code`). A sale (one reference number) holds many line items, and
 * specific UUC units are assigned to the buyer so any scanned code can be
 * traced back to the customer who purchased it.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Human-friendly identifier on each customer (e.g. CUS-000001).
        Schema::table('distributors', function (Blueprint $table) {
            if (!Schema::hasColumn('distributors', 'customer_code')) {
                $table->string('customer_code', 40)->nullable()->unique()->after('id');
            }
        });

        Schema::create('customer_sales', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 40)->unique();      // SAL-YYYYMMDD-000001
            $table->foreignId('customer_id')->constrained('distributors')->restrictOnDelete();
            $table->date('sale_date');
            $table->enum('status', ['draft', 'confirmed', 'delivered', 'cancelled'])->default('confirmed');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('total', 16, 2)->default(0);
            $table->unsignedInteger('units_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'sale_date']);
        });

        Schema::create('customer_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->timestamps();
        });

        // Unit-level linkage: which sale / customer a unit was sold to.
        Schema::table('batch_units', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_sale_id')->nullable()->after('status');
            $table->unsignedBigInteger('sold_to_id')->nullable()->after('customer_sale_id');
            $table->timestamp('sold_at')->nullable()->after('sold_to_id');

            $table->foreign('customer_sale_id')->references('id')->on('customer_sales')->nullOnDelete();
            $table->foreign('sold_to_id')->references('id')->on('distributors')->nullOnDelete();
            $table->index('sold_to_id');
        });
    }

    public function down(): void
    {
        Schema::table('batch_units', function (Blueprint $table) {
            $table->dropForeign(['customer_sale_id']);
            $table->dropForeign(['sold_to_id']);
            $table->dropColumn(['customer_sale_id', 'sold_to_id', 'sold_at']);
        });
        Schema::dropIfExists('customer_sale_items');
        Schema::dropIfExists('customer_sales');
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn('customer_code');
        });
    }
};
