<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Patient / partner requests to receive a genuine product's details by email.
     */
    public function up(): void
    {
        Schema::create('product_info_requests', function (Blueprint $table) {
            $table->id();
            $table->string('uuc_code')->index();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('partner_name')->nullable();
            $table->string('partner_phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->boolean('emailed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_info_requests');
    }
};
