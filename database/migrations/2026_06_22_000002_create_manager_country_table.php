<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A manager can manage many countries (many-to-many). Replaces the single
 * users.country_id assignment; existing values are migrated into the pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_country', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'country_id']);
        });

        // Seed the pivot from any existing single-country assignment.
        foreach (DB::table('users')->whereNotNull('country_id')->get(['id', 'country_id']) as $u) {
            DB::table('manager_country')->insertOrIgnore([
                'user_id'    => $u->id,
                'country_id' => $u->country_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_country');
    }
};
