<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Patient Portal — Patient Records & Prescription Dispensing
     *
     * Stores de-identified patient records and their dispensing history.
     * Prescription dispenses link a patient to a product batch so
     * adverse-event or recall traceability is possible.
     *
     * PII fields are marked for application-level encryption.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_ref')->unique();             // internal ref: PAT-2026-XXXXX

            // Personal — encrypt at application layer
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('national_id', 50)->nullable();       // encrypted
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();

            // Medical
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->string('blood_group', 5)->nullable();

            // Registration
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('distributor_id')                   // dispensing pharmacy / clinic
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_ref');
            $table->index(['last_name', 'first_name']);
            $table->index('distributor_id');
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_ref')->unique();        // RX-2026-XXXXX
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('prescribing_doctor')->nullable();
            $table->string('prescribing_facility')->nullable();
            $table->date('prescribed_date');
            $table->date('valid_until')->nullable();
            $table->text('diagnosis_notes')->nullable();
            $table->enum('status', ['active', 'dispensed', 'partially_dispensed', 'expired', 'cancelled'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('status');
        });

        Schema::create('prescription_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->string('dosage_instructions');
            $table->decimal('quantity_prescribed', 10, 2);
            $table->string('unit_of_measure', 30)->default('unit');
            $table->integer('duration_days')->nullable();
            $table->decimal('quantity_dispensed', 10, 2)->default(0);
            $table->boolean('is_fully_dispensed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('prescription_id');
        });

        Schema::create('dispensing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('distributor_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('quantity_dispensed', 10, 2);
            $table->string('unit_of_measure', 30)->default('unit');
            $table->string('lot_number', 60)->nullable();        // denormalised for quick recall lookup
            $table->date('expiry_date')->nullable();             // denormalised
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->decimal('total_price', 16, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamp('dispensed_at');
            $table->timestamps();

            $table->index('patient_id');
            $table->index('batch_id');
            $table->index('dispensed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensing_records');
        Schema::dropIfExists('prescription_lines');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('patients');
    }
};
