<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CountryManagerController extends Controller
{
    public const ROLES = [
        'super_admin'  => 'Administrator',
        'manufacturer' => 'Manufacturer',
        'logistics'    => 'Logistics',
        'finance'      => 'Finance',
        'qc_officer'   => 'QC Officer',
        'distributor'  => 'Country Manager',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'search'     => trim((string) $request->query('search', '')),
            'role'       => $request->query('role', ''),
            'country_id' => $request->query('country_id', ''),
            'per_page'   => (int) $request->query('per_page', 15),
        ];
        $perPage = in_array($filters['per_page'], [15, 30, 50, 100], true) ? $filters['per_page'] : 15;

        // This page manages country managers only.
        $query = User::with('countries')->where('role', 'country_manager');
        if ($filters['search'] !== '') {
            $s = $filters['search'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
        }
        if ($filters['country_id'] !== '') $query->whereHas('countries', fn ($q) => $q->where('countries.id', $filters['country_id']));

        $managers = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $base = fn () => User::where('role', 'country_manager');
        $stats = [
            'total'     => $base()->count(),
            'active'    => $base()->where('is_active', true)->count(),
            'assigned'  => $base()->has('countries')->count(),
            'countries' => \Illuminate\Support\Facades\DB::table('manager_country')
                ->whereIn('user_id', $base()->pluck('id'))->distinct()->count('country_id'),
        ];

        $countries = Country::orderBy('name')->get(['id', 'name', 'flag']);
        $roles     = self::ROLES;

        return view('country-managers.index', compact('managers', 'stats', 'filters', 'countries', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateManager($request);

        $countryIds = $data['country_ids'] ?? [];
        $user = new User();
        $user->forceFill([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password'] ?? str()->random(16)),
            'role'       => 'country_manager',
            'country_id' => $countryIds[0] ?? null,   // primary (legacy)
            'phone'      => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'initials'   => strtoupper(substr($data['name'], 0, 2)),
            'is_active'  => $request->boolean('is_active', true),
        ])->save();
        $user->syncRoles(['country_manager']);
        $user->countries()->sync($countryIds);

        return back()->with('success', "Country Manager '{$user->name}' added.");
    }

    public function update(Request $request, User $manager): RedirectResponse
    {
        $data = $this->validateManager($request, $manager->id);

        $countryIds = $data['country_ids'] ?? [];
        $manager->forceFill([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'role'       => 'country_manager',
            'country_id' => $countryIds[0] ?? null,   // primary (legacy)
            'phone'      => $data['phone'] ?? null,
            'department' => $data['department'] ?? null,
            'is_active'  => $request->boolean('is_active'),
        ]);
        if (!empty($data['password'])) {
            $manager->password = Hash::make($data['password']);
        }
        $manager->save();
        $manager->syncRoles(['country_manager']);
        $manager->countries()->sync($countryIds);

        return back()->with('success', "Country Manager '{$manager->name}' updated.");
    }

    public function destroy(User $manager): RedirectResponse
    {
        // Block deletion if the manager is referenced on any order document.
        $inUse = PurchaseOrder::where('created_by', $manager->id)->orWhere('acknowledged_by', $manager->id)->exists()
            || SalesOrder::where('created_by', $manager->id)->exists();

        if ($inUse) {
            $manager->forceFill(['is_active' => false])->save();
            return back()->with('warning', "'{$manager->name}' is linked to existing orders — deactivated instead of deleted.");
        }

        $name = $manager->name;
        $manager->delete();
        return back()->with('success', "Country Manager '{$name}' removed.");
    }

    private function validateManager(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name'          => 'required|string|max:120',
            'email'         => 'required|email|max:160|unique:users,email,' . ($id ?? 'NULL'),
            'country_ids'   => 'nullable|array',
            'country_ids.*' => 'exists:countries,id',
            'role'          => 'nullable|in:' . implode(',', array_keys(self::ROLES)),
            'phone'         => 'nullable|string|max:30',
            'department'    => 'nullable|string|max:120',
            'password'      => 'nullable|string|min:6|max:100',
            'is_active'     => 'nullable|boolean',
        ]);
    }
}
