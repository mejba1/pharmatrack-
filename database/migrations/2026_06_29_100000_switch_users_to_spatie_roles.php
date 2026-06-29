<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move access control from the per-user `users.permissions` (module-key JSON)
 * model to Spatie role-based permissions.
 *
 *  - Drop the legacy `users.permissions` column (replaced by model_has_roles /
 *    role_has_permissions). Values were exported to
 *    storage/legacy_user_permissions_backup.json beforehand.
 *  - Widen `users.role` from an enum to a plain string so it can mirror any
 *    Spatie role name, including custom roles created in the UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }

        // enum -> varchar (native change in Laravel 12)
        DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(60) NOT NULL DEFAULT 'distributor'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('super_admin','manufacturer','distributor','finance','logistics','qc_officer') NOT NULL DEFAULT 'distributor'");

        if (! Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('permissions')->nullable()->after('role');
            });
        }
    }
};
