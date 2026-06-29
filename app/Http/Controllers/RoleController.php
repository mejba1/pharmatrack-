<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Manages Spatie roles and their module-permission sets. Permissions map 1:1
 * to module keys in config/modules.php (see RolesAndPermissionsSeeder), so a
 * role's permissions are exactly the modules its users may access.
 */
class RoleController extends Controller
{
    /** Roles that ship with the system and may not be renamed or deleted. */
    private const PROTECTED_ROLES = ['super_admin'];

    // ── Create Role page: form + role list (CRUD) ─────────────────────────
    public function index(): View
    {
        $roles   = Role::withCount(['users', 'permissions'])->orderBy('name')->get();
        $modules = config('modules', []);

        return view('roles.index', compact('roles', 'modules'));
    }

    // ── Permission Set page: pick a role, check modules, submit ───────────
    public function permissions(): View
    {
        $roles   = Role::with('permissions')->orderBy('name')->get();
        $modules = config('modules', []);

        // role id => [module keys it currently has]
        $rolePermissions = $roles->mapWithKeys(
            fn ($r) => [$r->id => $r->permissions->pluck('name')->values()]
        );

        return view('roles.permissions', compact('roles', 'modules', 'rolePermissions'));
    }

    // ── Store: create a role (name only) ──────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:60']]);

        $name = Str::snake(Str::lower(trim($request->input('name'))));

        if (Role::where('name', $name)->exists()) {
            return back()->withErrors(['name' => 'A role with this name already exists.']);
        }

        Role::create(['name' => $name, 'guard_name' => 'web']);

        return redirect()->route('roles.index')->with('success', "Role '{$name}' created. Set its modules next.");
    }

    // ── Update: rename a role ─────────────────────────────────────────────
    public function update(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->withErrors(['name' => "The '{$role->name}' role is protected and can't be renamed."]);
        }

        $role->update(['name' => Str::snake(Str::lower(trim($request->input('name'))))]);

        return redirect()->route('roles.index')->with('success', "Role renamed to '{$role->name}'.");
    }

    // ── Sync module permissions for a role (Permission Set page) ──────────
    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        $modules = array_keys(config('modules', []));
        $data = $request->validate([
            'permissions'   => ['array'],
            'permissions.*' => ['string', Rule::in($modules)],
        ]);

        // Ensure each module key exists as a permission, then sync.
        foreach ($data['permissions'] ?? [] as $key) {
            Permission::firstOrCreate(['name' => $key, 'guard_name' => 'web']);
        }
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.permissions', ['role' => $role->id])
            ->with('success', "Modules updated for '{$role->name}' (" . count($data['permissions'] ?? []) . ' assigned).');
    }

    // ── Destroy ───────────────────────────────────────────────────────────
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return back()->withErrors(['role' => "The '{$role->name}' role is protected and cannot be deleted."]);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors(['role' => "Cannot delete '{$role->name}': it is still assigned to "
                . $role->users()->count() . ' user(s).']);
        }

        $name = $role->name;
        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role '{$name}' deleted.");
    }
}
