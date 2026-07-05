<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per-customer portal overrides (login access + ordering). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('portal_access')->default(true)->after('status');       // may this customer log in?
            $table->boolean('portal_can_order')->nullable()->after('portal_access'); // null = follow global, false = blocked
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['portal_access', 'portal_can_order']);
        });
    }
};
