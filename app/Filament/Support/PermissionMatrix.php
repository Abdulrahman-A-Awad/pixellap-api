<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The simple roles editor: friendly AREAS × ACTIONS ↔ Shield permission names.
 *
 *   view    → view_any_{id} + view_{id}
 *   create  → create_{id}
 *   update  → update_{id}
 *   delete  → delete_{id} + delete_any_{id}   (soft delete only: never restore / force_delete)
 *   reorder → reorder_{id}
 *
 * Technical permissions (replicate, restore, restore_any, force_delete, force_delete_any) and
 * widget permissions are never written: dashboard widgets follow their area's "view"
 * (see self::can()), and only a super admin restores or permanently deletes.
 *
 * Labels are English translation keys (lang/ar.json).
 */
final class PermissionMatrix
{
    /** Column order of the matrix. */
    public const ACTIONS = ['view', 'create', 'update', 'delete', 'reorder'];

    public const ACTION_LABELS = [
        'view' => 'View',
        'create' => 'Add',
        'update' => 'Edit',
        'delete' => 'Delete',
        'reorder' => 'Reorder',
    ];

    /** Roles the code relies on by name (notifications, refresh, seeder): they cannot be renamed or deleted. */
    public const BUILT_IN = [AppServiceProvider::SUPER_ADMIN_ROLE, 'admin', 'content_editor', 'sales'];

    /** Shown when a built-in role has no description of its own. */
    public const DEFAULT_DESCRIPTIONS = [
        AppServiceProvider::SUPER_ADMIN_ROLE => 'Full access to everything, including roles.',
        'admin' => 'Everything except managing roles.',
        'content_editor' => 'Edits website content and interface texts. Cannot delete.',
        'sales' => 'Reads and answers contact messages.',
    ];

    /**
     * key => label, help, icon, own identifiers, identifiers shared with another area
     * (written when this area grants them, but not used to read the checkbox state),
     * and the actions that exist for the area.
     *
     * @return array<string, array{label: string, help: string, icon: string, ids: list<string>, shared: list<string>, actions: list<string>, special?: array<string, list<string>>}>
     */
    public static function areas(): array
    {
        return [
            'messages' => [
                'label' => 'Messages', 'help' => 'Contact form leads and their status.', 'icon' => 'heroicon-o-inbox',
                'ids' => ['contact::message'], 'shared' => [], 'actions' => ['view', 'update', 'delete'],
            ],
            'home' => [
                'label' => 'Home page', 'help' => 'Home sections, numbers, steps, client logos and FAQs.', 'icon' => 'heroicon-o-rectangle-group',
                'ids' => ['home::section', 'stat', 'process::step', 'client', 'faq'], 'shared' => [],
                'actions' => self::ACTIONS,
            ],
            'services' => [
                'label' => 'Services', 'help' => 'Service categories, the services inside them and their FAQs.', 'icon' => 'heroicon-o-squares-2x2',
                'ids' => ['service::category', 'service'], 'shared' => ['faq'], 'actions' => self::ACTIONS,
            ],
            'pages' => [
                'label' => 'Pages', 'help' => 'About and other pages, and their content blocks.', 'icon' => 'heroicon-o-document-text',
                'ids' => ['page', 'page::block'], 'shared' => [], 'actions' => self::ACTIONS,
            ],
            'settings' => [
                'label' => 'Site settings', 'help' => 'Contact details, social links, logos and SEO.', 'icon' => 'heroicon-o-cog-6-tooth',
                'ids' => [], 'shared' => [], 'actions' => ['update'],
                'special' => ['update' => ['page_ManageSettings']],
            ],
            'ui' => [
                'label' => 'Interface texts', 'help' => 'Buttons, menus and short website texts.', 'icon' => 'heroicon-o-language',
                'ids' => ['ui::string'], 'shared' => [], 'actions' => ['view', 'update'],
            ],
            'users' => [
                'label' => 'Users', 'help' => 'Staff accounts that can sign in here.', 'icon' => 'heroicon-o-users',
                'ids' => ['user'], 'shared' => [], 'actions' => ['view', 'create', 'update', 'delete'],
            ],
            'roles' => [
                'label' => 'Roles', 'help' => 'This screen: who can do what.', 'icon' => 'heroicon-o-shield-check',
                'ids' => ['role'], 'shared' => [], 'actions' => ['view', 'create', 'update', 'delete'],
            ],
            'activity' => [
                'label' => 'Activity log', 'help' => 'History of changes made in the admin.', 'icon' => 'heroicon-o-clipboard-document-list',
                'ids' => ['activity::log'], 'shared' => [], 'actions' => ['view', 'delete'],
            ],
        ];
    }

    public static function applies(string $area, string $action): bool
    {
        return in_array($action, self::areas()[$area]['actions'] ?? [], true);
    }

    /**
     * Permission names for one cell.
     *
     * @return list<string>
     */
    public static function permissionsFor(string $area, string $action, bool $withShared = true): array
    {
        $config = self::areas()[$area] ?? null;
        if ($config === null || ! self::applies($area, $action)) {
            return [];
        }

        if (isset($config['special'][$action])) {
            return $config['special'][$action];
        }

        $prefixes = match ($action) {
            'view' => ['view_any', 'view'],
            'delete' => ['delete', 'delete_any'],
            default => [$action],
        };

        $ids = $withShared ? [...$config['ids'], ...$config['shared']] : $config['ids'];

        $out = [];
        foreach ($ids as $id) {
            foreach ($prefixes as $prefix) {
                $out[] = "{$prefix}_{$id}";
            }
        }

        return $out;
    }

    /** @return array<string, array<string, bool>> every applicable cell off */
    public static function emptyState(): array
    {
        return collect(self::areas())
            ->map(fn (array $c): array => array_fill_keys($c['actions'], false))
            ->all();
    }

    /**
     * Matrix state → the permission names to store on the role.
     *
     * @param  array<string, array<string, mixed>>  $state
     * @return list<string>
     */
    public static function toPermissions(array $state): array
    {
        $names = [];
        foreach (self::areas() as $area => $config) {
            foreach ($config['actions'] as $action) {
                if (filter_var($state[$area][$action] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    array_push($names, ...self::permissionsFor($area, $action));
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Stored permission names → matrix state. A cell is on when all of the area's own permissions are there.
     *
     * @param  iterable<string>  $permissions
     * @return array<string, array<string, bool>>
     */
    public static function fromPermissions(iterable $permissions): array
    {
        $have = collect($permissions)->flip();

        return collect(self::areas())
            ->map(fn (array $c, string $area): array => collect($c['actions'])
                ->mapWithKeys(fn (string $action): array => [$action => collect(self::permissionsFor($area, $action, withShared: false))
                    ->every(fn (string $p): bool => $have->has($p))])
                ->all())
            ->all();
    }

    /** Writes the matrix onto the role (creating missing permission rows). */
    public static function sync(Role $role, array $state): void
    {
        $names = self::toPermissions($state);
        foreach ($names as $name) {
            Permission::findOrCreate($name, $role->guard_name ?: 'web');
        }

        $role->syncPermissions($names);
    }

    /** Can the signed-in user use this area (super admins always)? Used by dashboard widgets and quick actions. */
    public static function can(string $area, string $action = 'view', ?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user instanceof User) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        $needed = self::permissionsFor($area, $action, withShared: false);

        return $needed !== [] && collect($needed)->every(fn (string $p): bool => $user->checkPermissionTo($p, 'web'));
    }

    public static function isSuperAdminRole(?Role $role): bool
    {
        return $role?->name === AppServiceProvider::SUPER_ADMIN_ROLE;
    }

    public static function isBuiltIn(?Role $role): bool
    {
        return $role !== null && in_array($role->name, self::BUILT_IN, true);
    }

    /** "content_editor" → "Content Editor" (translated); custom role names are shown as typed. */
    public static function roleLabel(string $name): string
    {
        return in_array($name, self::BUILT_IN, true) ? __(str($name)->headline()->toString()) : $name;
    }

    public static function roleDescription(Role $role): ?string
    {
        $own = trim((string) ($role->getAttribute('description') ?? ''));
        if ($own !== '') {
            return $own;
        }

        return isset(self::DEFAULT_DESCRIPTIONS[$role->name]) ? __(self::DEFAULT_DESCRIPTIONS[$role->name]) : null;
    }

    /** Number of switched-on cells, for the roles list. */
    public static function countOn(Collection|array $permissions): int
    {
        return collect(self::fromPermissions($permissions))->flatten()->filter()->count();
    }
}
