<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assign each customer to an account / country manager (a user). Super admin
 * sets this; a manager then sees only the customers assigned to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            if (!Schema::hasColumn('distributors', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->after('country_id')->constrained('users')->nullOnDelete();
                $table->index('manager_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn('manager_id');
        });
    }
};
