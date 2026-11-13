<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Role that bypasses every permission check.
     */
    public const SUPER_ADMIN_ROLE = 'super_admin';

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
        // Super Admin passes every Gate / policy check. Returning null lets
        // normal permission checks run for everyone else.
        Gate::before(
            fn ($user): ?bool => $user instanceof User && $user->hasRole(self::SUPER_ADMIN_ROLE) ? true : null,
        );
    }
}
