<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'  => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** Primary managed country (legacy single assignment). */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /** All countries this manager covers (many-to-many). */
    public function countries()
    {
        return $this->belongsToMany(Country::class, 'manager_country')->withTimestamps();
    }

    // ── Access control ─────────────────────────────────────────────────────
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Can this user access the given module key? Access is granted via the
     * user's Spatie role(s); each module key is registered as a permission.
     * super_admin always can (also enforced by the Gate::before god-mode).
     */
    public function canModule(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        // checkPermissionTo() returns false instead of throwing for unknown keys.
        return $this->checkPermissionTo($key);
    }

    /** True when this user sees all data (no per-user row scoping). */
    public function seesAllData(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Can this user see ALL records in the given module (not just their own)?
     * super_admin always can; otherwise it requires the "{module}.view_all"
     * scope permission granted by a super admin (via role or per-user).
     */
    public function canViewAll(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->checkPermissionTo("{$module}.view_all");
    }

    /** IDs of countries this manager covers (for customer scoping). */
    public function managedCountryIds(): array
    {
        return $this->countries()->pluck('countries.id')->all();
    }

    protected ?array $ownedCustomerIdsMemo = null;

    /**
     * The set of customers this user is responsible for — the single source of
     * truth for account-manager / country-manager scoping. It is the union of
     * customers directly assigned to them (customers.manager_id) and customers
     * located in the countries they manage. Memoised for the request.
     *
     * @return array<int>
     */
    public function ownedCustomerIds(): array
    {
        if ($this->ownedCustomerIdsMemo !== null) {
            return $this->ownedCustomerIdsMemo;
        }

        $countryIds = $this->managedCountryIds();

        $ids = \App\Models\Customer::query()
            ->where('manager_id', $this->id)
            ->when($countryIds, fn ($q) => $q->orWhereIn('country_id', $countryIds))
            ->pluck('id')->map(fn ($i) => (int) $i)->all();

        return $this->ownedCustomerIdsMemo = $ids;
    }

    /** Resolve a route name to its module key via config/modules.php (or null). */
    public static function moduleForRoute(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }
        foreach (config('modules', []) as $key => $cfg) {
            foreach ($cfg['prefixes'] as $prefix) {
                $base = rtrim($prefix, '.');
                if ($routeName === $base || str_starts_with($routeName, $base . '.')) {
                    return $key;
                }
            }
        }
        return null;
    }
}
