<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pre-aggregated daily verification rollups. Dashboards/reports read these
     * small rows instead of scanning the (potentially millions-of-rows)
     * verification_logs table. Rebuilt by `anti-counterfeit:rollup`.
     */
    public function up(): void
    {
        Schema::create('verification_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('day')->unique();
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('genuine')->default(0);
            $table->unsignedBigInteger('suspicious')->default(0);
            $table->unsignedBigInteger('invalid')->default(0);   // invalid + expired + recalled + locked
            $table->unsignedBigInteger('alerts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_daily_stats');
    }
};
