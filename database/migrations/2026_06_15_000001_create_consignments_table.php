<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consignment (internal "Shipment") — a higher-level aggregation that groups
     * many Master Cartons into one logistics unit (SHP-2026-00001) with its own
     * QR. Scanning the parent QR reveals every carton, product, batch and unit it
     * carries, plus dispatch and receiving status — so a depot can verify a
     * 100-carton delivery with a single scan instead of 100.
     *
     * Named "consignments" to avoid colliding with the existing international
     * freight `shipments` table (commercial invoice / bill of lading / customs).
     */
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('consignment_number')->unique();  // SHP-2026-00001
            $table->string('qr_code')->unique();             // parent scan token
            $table->string('origin')->default('Factory');
            $table->string('destination')->nullable();       // Head Office / Depot / …
            $table->string('carrier')->nullable();
            $table->string('vehicle_no')->nullable();

            $table->enum('status', [
                'created', 'dispatched', 'in_transit', 'received', 'closed',
            ])->default('created');

            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('qr_code');
        });

        // A carton belongs to at most one consignment (nullable until assigned).
        Schema::table('master_cartons', function (Blueprint $table) {
            $table->foreignId('consignment_id')->nullable()->after('id')
                  ->constrained('consignments')->nullOnDelete();
            $table->index('consignment_id');
        });

        // Consignment-level scan / movement log.
        Schema::create('consignment_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->string('event');                         // created / dispatched / received / scanned …
            $table->string('performed_by')->nullable();
            $table->string('location')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('consignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_scans');

        Schema::table('master_cartons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consignment_id');
        });

        Schema::dropIfExists('consignments');
    }
};
