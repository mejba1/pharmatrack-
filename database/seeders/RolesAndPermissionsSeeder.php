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

        // 1. One permission per module key (role-based module access).
        $modules = array_keys(config('modules', []));
        foreach ($modules as $key) {
            Permission::firstOrCreate(['name' => $key, 'guard_name' => 'web']);
        }

        // 1b. Fine-grained "{module}.{action}" permissions, granted per-user.
        $actions = array_keys(config('abilities.actions', []));
        foreach ($modules as $key) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$key}.{$action}", 'guard_name' => 'web']);
            }
        }

        // 1c. Visibility scopes ("{module}.view_all"). Created but NOT granted
        //     to roles by default — super admin assigns them selectively so a
        //     user can see all records in an area instead of just their own.
        $scopes = array_keys(config('abilities.scopes', []));
        foreach ($modules as $key) {
            foreach ($scopes as $scope) {
                Permission::firstOrCreate(['name' => "{$key}.{$scope}", 'guard_name' => 'web']);
            }
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

    /**
     * Sync a role to the given modules: grants module access plus every
     * action ({module}.{action}) for those modules, so the role carries full
     * CRUD by default. Admins can fine-tune per role on the Permission Set page.
     *
     * @param  list<string>  $modules
     */
    protected function syncRole(string $name, array $modules): void
    {
        $actions = array_keys(config('abilities.actions', []));

        $permissions = [];
        foreach ($modules as $module) {
            $permissions[] = $module; // module access
            foreach ($actions as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
    }
}
