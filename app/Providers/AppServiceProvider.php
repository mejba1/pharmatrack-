<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Render all paginators with Bootstrap 5 markup (matches the app's UI).
        Paginator::useBootstrapFive();

        // Super admin can do everything — short-circuit every Gate/permission
        // check (module access, role/permission management, user admin, etc.).
        Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);
    }
}
