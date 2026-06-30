<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /** Available roles as [name => Label], sourced from Spatie. */
    private function roleOptions(): array
    {
        return Role::orderBy('name')->pluck('name')
            ->mapWithKeys(fn ($n) => [$n => Str::headline(str_replace('_', ' ', $n))])
            ->all();
    }

    public function index(Request $request): View
    {
        $filters = [
            'search'   => trim((string) $request->query('search', '')),
            'role'     => $request->query('role', ''),
            'per_page' => (int) $request->query('per_page', 15),
        ];
        $perPage = in_array($filters['per_page'], [15, 30, 50, 100], true) ? $filters['per_page'] : 15;

        $query = User::query()->with(['roles.permissions', 'permissions']);
        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($filters['role'] !== '') $query->where('role', $filters['role']);

        $users = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $stats = [
            'total'    => User::count(),
            'active'   => User::where('is_active', true)->count(),
            'admins'   => User::where('role', 'super_admin')->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        $roles   = $this->roleOptions();
        $modules = config('modules', []);          // module key => [label, ...]
        $actions = config('abilities.actions', []); // action key => Label

        return view('users.index', compact('users', 'stats', 'filters', 'roles', 'modules', 'actions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        $user = new User();
        $user->forceFill([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password'] ?: str()->random(16)),
            'role'       => $data['role'] ?? 'distributor',
            'phone'      => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'initials'   => strtoupper(substr($data['name'], 0, 2)),
            'is_active'  => $request->boolean('is_active', true),
        ])->save();

        $user->syncRoles([$user->role]);
        $user->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', "User '{$user->name}' created with the '{$user->role}' role.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateUser($request, $user->id);

        $user->forceFill([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'role'       => $data['role'] ?? $user->role,
            'phone'      => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'is_active'  => $request->boolean('is_active'),
        ]);
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $user->syncRoles([$user->role]);
        $user->syncPermissions($data['permissions'] ?? []);

        return back()->with('success', "User '{$user->name}' updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if (User::where('role', 'super_admin')->count() <= 1 && $user->role === 'super_admin') {
            return back()->with('error', 'Cannot delete the last Super Admin.');
        }

        $inUse = PurchaseOrder::where('created_by', $user->id)->orWhere('acknowledged_by', $user->id)->exists()
            || SalesOrder::where('created_by', $user->id)->exists();
        if ($inUse) {
            $user->forceFill(['is_active' => false])->save();
            return back()->with('warning', "'{$user->name}' is linked to existing orders — deactivated instead of deleted.");
        }

        $name = $user->name;
        $user->delete();
        return back()->with('success', "User '{$name}' removed.");
    }

    private function validateUser(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name'          => 'required|string|max:120',
            'email'         => 'required|email|max:160|unique:users,email,' . ($id ?? 'NULL'),
            'role'          => 'nullable|string|exists:roles,name',
            'phone'         => 'nullable|string|max:30',
            'department'    => 'nullable|string|max:120',
            'password'      => 'nullable|string|min:6|max:100',
            'is_active'     => 'nullable|boolean',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
    }
}
