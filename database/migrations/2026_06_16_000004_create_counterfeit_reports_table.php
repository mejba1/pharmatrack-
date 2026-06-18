<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public counterfeit / problem reports submitted from the verification page.
     */
    public function up(): void
    {
        Schema::create('counterfeit_reports', function (Blueprint $table) {
            $table->id();
            $table->string('uuc_code')->index();
            $table->foreignId('verification_log_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();          // block code: fake / country / recalled …
            $table->string('reporter_name');
            $table->string('reporter_phone');
            $table->string('reporter_email')->nullable();
            $table->text('message')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('status')->default('new')->index();  // new | reviewing | resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterfeit_reports');
    }
};
