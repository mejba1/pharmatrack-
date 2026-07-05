<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // When true, this product verifies anywhere: bypasses Access-Control
            // locks, allowed-countries, and Country Authorization ("Open wins").
            $table->boolean('verify_open')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('verify_open');
        });
    }
};
