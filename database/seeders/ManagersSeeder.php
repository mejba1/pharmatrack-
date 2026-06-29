<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a handful of internal manager / handler accounts so Purchase Orders
 * (and later order documents) can be attributed to a `created_by` user while
 * login is not yet enforced. Idempotent — safe to run repeatedly.
 */
class ManagersSeeder extends Seeder
{
    public function run(): void
    {
        // A broad default permission set (everything except user administration)
        // so non-admin demo accounts can actually use the app.
        $all = array_keys(config('modules', []));
        // Standard managers: everything except user admin and invoice (PI/CI) management.
        $standard = array_values(array_diff($all, ['users', 'invoices']));

        $managers = [
            ['name' => 'System Administrator', 'email' => 'admin@pharmatrack.local', 'role' => 'super_admin',  'initials' => 'SA', 'department' => 'Administration',         'permissions' => []],
            ['name' => 'Imran Hossain',        'email' => 'imran@pharmatrack.local', 'role' => 'manufacturer', 'initials' => 'IH', 'department' => 'Export / Manufacturing', 'permissions' => $standard],
            ['name' => 'Farah Khan',           'email' => 'farah@pharmatrack.local', 'role' => 'logistics',    'initials' => 'FK', 'department' => 'Logistics',             'permissions' => ['orders', 'logistics', 'master_cartons', 'customers']],
            ['name' => 'Rahim Uddin',          'email' => 'rahim@pharmatrack.local', 'role' => 'finance',      'initials' => 'RU', 'department' => 'Finance',               'permissions' => ['orders', 'reports', 'customers']],
        ];

        foreach ($managers as $m) {
            $user = User::firstOrNew(['email' => $m['email']]);
            $user->forceFill([
                'name'        => $m['name'],
                'email'       => $m['email'],
                'password'    => Hash::make('password'),   // demo password for all seeded accounts
                'role'        => $m['role'],
                'permissions' => $m['permissions'],
                'initials'    => $m['initials'],
                'department'  => $m['department'],
                'is_active'   => true,
                'deleted_at'  => null,
            ])->save();
        }
    }
}
