<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eligibility allowlists. NULL / empty = applies to everyone / everything.
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->json('customer_ids')->nullable()->after('product_id'); // limit to these customers
            $table->json('country_ids')->nullable()->after('customer_ids'); // limit to these countries
            $table->json('product_ids')->nullable()->after('country_ids');  // order must include one of these products
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn(['customer_ids', 'country_ids', 'product_ids']);
        });
    }
};
