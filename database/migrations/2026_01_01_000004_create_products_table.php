<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product Master — one row per SKU
     * PRN (Product Registration Number) stored here as the global identifier.
     * Per-country registrations live in product_country_registrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('prn')->unique();                    // e.g. PRN-US-ANT-00142
            $table->string('name');                             // e.g. "Amoxil 500mg Capsules"
            $table->string('generic_name')->nullable();         // e.g. "Amoxicillin"
            $table->string('brand_name')->nullable();
            $table->enum('dosage_form', [
                'tablet', 'capsule', 'injection', 'syrup',
                'cream', 'ointment', 'drops', 'inhaler', 'other',
            ]);
            $table->string('strength')->nullable();             // e.g. "500mg"
            $table->string('pack_size')->nullable();            // e.g. "10 tabs/blister, 10 blisters"
            $table->string('atc_code', 20)->nullable();         // WHO ATC classification
            $table->string('hs_code', 20)->nullable();          // Harmonised System code for customs
            $table->enum('controlled_substance', ['no', 'schedule_1', 'schedule_2', 'schedule_3'])
                  ->default('no');

            $table->string('manufacturer_name')->nullable();
            $table->string('manufacturing_site')->nullable();
            $table->string('country_of_origin', 5)->nullable(); // ISO code

            $table->string('shelf_life')->nullable();           // e.g. "36 months"
            $table->string('storage_conditions')->nullable();   // e.g. "Store below 25°C"
            $table->enum('temperature_sensitivity', [
                'ambient', 'cool_chain', 'cold_chain', 'frozen',
            ])->default('ambient');

            $table->decimal('unit_cost', 12, 4)->nullable();    // base cost USD
            $table->string('unit_of_measure', 20)->default('unit');

            $table->enum('status', ['active', 'discontinued', 'pending_approval'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('dosage_form');
            $table->fullText(['name', 'generic_name', 'brand_name']);
        });

        // Per-country product registrations
        Schema::create('product_country_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('local_registration_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['approved', 'pending', 'rejected', 'expired'])->default('approved');
            $table->timestamps();

            $table->unique(['product_id', 'country_id']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_country_registrations');
        Schema::dropIfExists('products');
    }
};
