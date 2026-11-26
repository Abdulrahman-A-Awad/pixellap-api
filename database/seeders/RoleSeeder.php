<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles only — permissions are generated later by Filament Shield.
 */
class RoleSeeder extends Seeder
{
    public const ROLES = [AppServiceProvider::SUPER_ADMIN_ROLE, 'admin', 'content_editor', 'sales'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $name) {
            Role::findOrCreate($name, 'web');
        }

        // Production never gets a default account: create one with `php artisan pixellap:create-super-admin`.
        if (app()->isProduction()) {
            return;
        }

        // Local/testing convenience super admin. Existing users keep their current password.
        $admin = User::firstOrCreate(
            ['email' => 'admin@pixellap.test'],
            // English UI for the local account (tests assert English labels); real accounts default to Arabic.
            ['name' => 'Super Admin', 'password' => 'password', 'is_active' => true, 'admin_locale' => 'en'],
        );

        if (! $admin->hasRole(AppServiceProvider::SUPER_ADMIN_ROLE)) {
            $admin->assignRole(AppServiceProvider::SUPER_ADMIN_ROLE);
        }
    }
}
