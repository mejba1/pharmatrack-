<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'password'    => 'hashed',
            'permissions' => 'array',
            'is_active'   => 'boolean',
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

    /** Can this user access the given module key? super_admin always can. */
    public function canModule(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return in_array($key, (array) $this->permissions, true);
    }

    /** True when this user sees all data (no per-user row scoping). */
    public function seesAllData(): bool
    {
        return $this->isSuperAdmin();
    }

    /** IDs of countries this manager covers (for customer scoping). */
    public function managedCountryIds(): array
    {
        return $this->countries()->pluck('countries.id')->all();
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
