<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('flag', 16)->nullable()->after('code');       // emoji, e.g. 🇺🇸
            $table->string('dial_code', 10)->nullable()->after('flag');  // calling code, e.g. +1
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['flag', 'dial_code']);
        });
    }
};
