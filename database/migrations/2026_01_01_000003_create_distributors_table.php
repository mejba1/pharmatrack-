<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distribution hierarchy
     * Supports multi-level tree: Manufacturer → Distributor → Sub-distributor → Retailer
     */
    public function up(): void
    {
        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();  // self-referencing for hierarchy
            $table->foreignId('country_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->enum('type', [
                'manufacturer',
                'national_distributor',
                'regional_distributor',
                'sub_distributor',
                'retailer',
                'hospital',
                'pharmacy',
            ]);
            $table->string('license_number')->nullable()->unique();
            $table->string('gmp_certificate_number')->nullable();
            $table->date('license_expiry')->nullable();

            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->text('address')->nullable();

            $table->enum('status', ['active', 'suspended', 'expired', 'pending'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('distributors')->nullOnDelete();
            $table->index('parent_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributors');
    }
};
