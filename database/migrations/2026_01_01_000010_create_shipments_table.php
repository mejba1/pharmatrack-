<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shipments  (SHP-2026-XXXX)
     * Created from an approved Commercial Invoice.
     * shipment_events captures the step-by-step tracking timeline shown in the UI.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number')->unique();         // e.g. SHP-2026-0482

            // Upstream link
            $table->foreignId('commercial_invoice_id')
                  ->unique()
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Routing
            $table->string('origin_country', 5)->nullable();
            $table->string('origin_port')->nullable();           // e.g. "Port of Los Angeles"
            $table->string('destination_country', 5)->nullable();
            $table->string('destination_port')->nullable();      // e.g. "Port of Manila"

            // Carrier / forwarder
            $table->enum('mode', ['sea', 'air', 'road', 'rail', 'courier'])->default('sea');
            $table->string('carrier_name')->nullable();          // e.g. "Maersk"
            $table->string('vessel_or_flight')->nullable();      // vessel name or flight number
            $table->string('container_number')->nullable();
            $table->string('bill_of_lading_number')->nullable(); // B/L or AWB
            $table->string('tracking_number')->nullable();

            // Dates
            $table->date('booking_date')->nullable();
            $table->date('departure_date')->nullable();
            $table->date('estimated_arrival_date')->nullable();
            $table->date('actual_arrival_date')->nullable();

            // Clearance
            $table->boolean('customs_cleared')->default(false);
            $table->date('customs_cleared_date')->nullable();
            $table->string('customs_declaration_number')->nullable();

            // Physical
            $table->integer('total_packages')->nullable();
            $table->decimal('gross_weight_kg', 10, 3)->nullable();
            $table->decimal('volume_cbm', 10, 3)->nullable();    // cubic metres

            $table->enum('status', [
                'draft',
                'booked',
                'in_transit',
                'customs_hold',
                'delivered',
                'delayed',
                'cancelled',
            ])->default('draft');

            $table->string('assigned_to')->nullable();           // logistics officer name
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('departure_date');
            $table->index('estimated_arrival_date');
        });

        // ── Shipment Tracking Events (Timeline) ───────────────────
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();

            $table->string('event_type');                        // e.g. "departure", "customs_cleared"
            $table->string('title');                             // e.g. "Departed Port of LA"
            $table->text('description')->nullable();
            $table->string('location')->nullable();              // e.g. "Los Angeles, USA"
            $table->timestamp('event_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_milestone')->default(false);     // shown prominently in timeline
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('event_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipments');
    }
};
