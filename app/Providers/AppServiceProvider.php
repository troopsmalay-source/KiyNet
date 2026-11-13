<?php

namespace App\Providers;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // PermissionCatalog backs ability checks such as Gate::authorize('customer.view').
        // Returning null lets model Policies decide for abilities not in the catalog.
        Gate::before(function (User $user, string $ability): ?bool {
            return PermissionCatalog::allows($user->role?->name, $ability) ? true : null;
        });
    }
}
