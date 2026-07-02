<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 160);
            $table->string('account_name', 160)->nullable();   // account holder / beneficiary
            $table->string('account_number', 60)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->string('iban', 60)->nullable();
            $table->string('branch', 160)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
