<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Promote the old `distributors` table to a first-class `customers` table.
 *
 * Renaming keeps every existing foreign key (orders, sales, batch units,
 * patients, scans, vault …) pointing at the same rows, so no data is lost and
 * the order flow keeps working through the Customer model. We then add the
 * richer customer fields and the columns needed for customer self-login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('distributors', 'customers');

        // type: enum -> varchar, remap legacy values to the new set
        // (agent / distributor / gpsp / local).
        DB::statement("ALTER TABLE `customers` MODIFY `type` VARCHAR(30) NOT NULL DEFAULT 'local'");
        DB::statement("UPDATE `customers` SET `type` = CASE
            WHEN `type` LIKE '%distributor%' OR `type` = 'manufacturer' THEN 'distributor'
            WHEN `type` IN ('retailer','pharmacy','hospital') THEN 'local'
            ELSE 'local' END");

        Schema::table('customers', function (Blueprint $t) {
            $t->string('email')->nullable()->after('name');
            $t->string('phone', 40)->nullable()->after('email');
            $t->string('city', 120)->nullable()->after('country_id');
            $t->string('company_id', 100)->nullable()->after('company_name');     // company registration / trade id
            $t->string('identification_type', 60)->nullable()->after('company_id');
            $t->string('identification_number', 100)->nullable()->after('identification_type');
            $t->string('referenced_by', 160)->nullable()->after('identification_number');
            $t->string('company_logo')->nullable()->after('referenced_by');

            // Self-login support (customer portal/dashboard).
            $t->string('password')->nullable()->after('company_logo');
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->rememberToken();
        });

        // Seed the new email/phone from the existing contact fields.
        DB::statement("UPDATE `customers` SET `email` = `contact_email` WHERE (`email` IS NULL OR `email` = '') AND `contact_email` IS NOT NULL");
        DB::statement("UPDATE `customers` SET `phone` = `contact_phone` WHERE (`phone` IS NULL OR `phone` = '') AND `contact_phone` IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->dropColumn([
                'email', 'phone', 'city', 'company_id', 'identification_type',
                'identification_number', 'referenced_by', 'company_logo',
                'password', 'email_verified_at', 'last_login_at', 'remember_token',
            ]);
        });

        DB::statement("ALTER TABLE `customers` MODIFY `type` ENUM('manufacturer','national_distributor','regional_distributor','sub_distributor','retailer','hospital','pharmacy') NOT NULL DEFAULT 'regional_distributor'");

        Schema::rename('customers', 'distributors');
    }
};
