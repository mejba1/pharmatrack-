<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Default module sets per built-in role. Module keys come from
     * config/modules.php; each key is registered as a Spatie permission so
     * User::canModule() (and the EnsureModuleAccess middleware) resolve to it.
     *
     * @var array<string, list<string>>
     */
    protected array $roleModules = [
        'manufacturer' => ['products', 'batches', 'master_cartons', 'master_data', 'anti_counterfeit', 'vault', 'reports', 'notifications'],
        'distributor'  => ['orders', 'invoices', 'customers', 'logistics', 'patients', 'vault', 'reports', 'notifications'],
        'finance'      => ['orders', 'invoices', 'reports', 'vault', 'notifications'],
        'logistics'    => ['logistics', 'master_cartons', 'orders', 'reports', 'notifications'],
        'qc_officer'   => ['products', 'batches', 'anti_counterfeit', 'compliance', 'reports', 'notifications'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. One permission per module key.
        $modules = array_keys(config('modules', []));
        foreach ($modules as $key) {
            Permission::firstOrCreate(['name' => $key, 'guard_name' => 'web']);
        }

        // 2. super_admin gets every module (also bypasses via Gate::before).
        $this->syncRole('super_admin', $modules);

        // 3. Built-in roles with their module sets.
        foreach ($this->roleModules as $role => $mods) {
            $this->syncRole($role, $mods);
        }

        // 4. Give every existing user the Spatie role that matches their
        //    legacy `role` column, so access keeps working after the switch.
        User::query()->each(function (User $user) {
            $role = $user->role ?: 'distributor';
            if (Role::where('name', $role)->exists()) {
                $user->syncRoles([$role]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @param list<string> $permissions */
    protected function syncRole(string $name, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
    }
}
