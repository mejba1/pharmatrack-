<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            $table->string('scope', 10)->default('total');       // product | total | none
            $table->string('discount_type', 10)->default('percent'); // percent | fixed
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); // for scope=product
            $table->decimal('min_order_value', 15, 2)->nullable();
            $table->decimal('max_discount', 15, 2)->nullable();   // cap for percentage
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Promo / discount snapshot carried on the purchase order it was applied to.
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('remarks')->constrained()->nullOnDelete();
            $table->string('promo_code', 40)->nullable()->after('promo_code_id');
            $table->string('discount_scope', 10)->nullable()->after('promo_code'); // product | total | none
            $table->string('discount_type', 10)->nullable()->after('discount_scope');
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
            $table->foreignId('discount_product_id')->nullable()->after('discount_value')->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
            $table->dropConstrainedForeignId('discount_product_id');
            $table->dropColumn(['promo_code', 'discount_scope', 'discount_type', 'discount_value']);
        });
        Schema::dropIfExists('promo_codes');
    }
};
