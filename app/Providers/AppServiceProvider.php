<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

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
        // Vendor model: not auto-discovered from App\Policies.
        Gate::policy(Activity::class, ActivityPolicy::class);

        Gate::before(function ($user, string $ability, array $arguments): ?bool {
            if (! $user instanceof User) {
                return null;
            }

            // Nobody — not even a super admin — can delete their own account.
            if (in_array($ability, ['delete', 'forceDelete'], true)
                && ($arguments[0] ?? null) instanceof User
                && $arguments[0]->is($user)) {
                return false;
            }

            // Super Admin passes every other Gate / policy check. Returning null
            // lets normal permission checks run for everyone else.
            return $user->hasRole(self::SUPER_ADMIN_ROLE) ? true : null;
        });
    }
}
