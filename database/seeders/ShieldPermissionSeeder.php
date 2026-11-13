<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role → permission matrix. Idempotent: roles are synced to exactly this set on every run.
 *
 *   super_admin    everything (Gate::before — no permissions stored)
 *   admin          everything except managing Shield roles
 *   content_editor view/create/update/reorder content, edit interface texts; never delete
 *   sales          contact messages: view + update only
 */
class ShieldPermissionSeeder extends Seeder
{
    /** Permission identifiers of content managed by editors (Shield format). */
    public const CONTENT = [
        'home::section', 'stat', 'process::step', 'client', 'faq',
        'service::category', 'service', 'page', 'page::block',
    ];

    /** Models edited through relation managers — Shield does not generate these. */
    public const CUSTOM = ['service', 'page::block'];

    public function run(): void
    {
        // 1. Make sure every resource/page/widget permission exists (policies are NOT touched).
        Artisan::call('shield:generate', [
            '--all' => true,
            '--option' => 'permissions',
            '--panel' => 'admin',
            '--no-interaction' => true,
        ]);

        // 2. Custom permissions for relation-manager-only models.
        $prefixes = config('filament-shield.permission_prefixes.resource');
        foreach (self::CUSTOM as $identifier) {
            foreach ($prefixes as $prefix) {
                Permission::findOrCreate("{$prefix}_{$identifier}", 'web');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = Permission::query()->where('guard_name', 'web')->pluck('name');

        // 3. Role matrix.
        $this->sync('admin', $all->reject(fn (string $p): bool => str_ends_with($p, '_role'))->all());

        $this->sync('content_editor', [
            ...$this->crossJoin(['view_any', 'view', 'create', 'update', 'reorder'], self::CONTENT),
            'view_any_ui::string', 'view_ui::string', 'update_ui::string',
        ]);

        $this->sync('sales', [
            'view_any_contact::message', 'view_contact::message', 'update_contact::message',
        ]);

        // super_admin keeps no explicit permissions; Gate::before grants everything.
        Role::findByName(AppServiceProvider::SUPER_ADMIN_ROLE, 'web')->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $prefixes
     * @param  list<string>  $identifiers
     * @return list<string>
     */
    private function crossJoin(array $prefixes, array $identifiers): array
    {
        $out = [];
        foreach ($identifiers as $identifier) {
            foreach ($prefixes as $prefix) {
                $out[] = "{$prefix}_{$identifier}";
            }
        }

        return $out;
    }

    /** @param  list<string>  $permissions */
    private function sync(string $role, array $permissions): void
    {
        Role::findOrCreate($role, 'web')->syncPermissions($permissions);
    }
}
