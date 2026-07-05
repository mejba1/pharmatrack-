<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Denormalized rollups so the shipment list never has to hydrate carton
     * rows to show a count / unit total. Maintained by the controller on
     * assign / remove / move, with a backfill here for existing rows.
     */
    public function up(): void
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->unsignedInteger('cartons_count')->default(0)->after('status');
            $table->unsignedInteger('units_count')->default(0)->after('cartons_count');
        });

        // Backfill from current carton assignments.
        $rollups = DB::table('master_cartons')
            ->select('consignment_id', DB::raw('COUNT(*) c'), DB::raw('COALESCE(SUM(packed_quantity),0) u'))
            ->whereNotNull('consignment_id')
            ->groupBy('consignment_id')
            ->get();

        foreach ($rollups as $r) {
            DB::table('consignments')->where('id', $r->consignment_id)
                ->update(['cartons_count' => $r->c, 'units_count' => $r->u]);
        }
    }

    public function down(): void
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->dropColumn(['cartons_count', 'units_count']);
        });
    }
};
