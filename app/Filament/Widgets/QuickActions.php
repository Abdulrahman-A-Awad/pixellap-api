<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\HomeSectionKey;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\HomeSectionResource;
use App\Filament\Resources\ServiceCategoryResource;
use App\Filament\Resources\UiStringResource;
use App\Filament\Resources\UserResource;
use App\Filament\Support\PermissionMatrix;
use App\Models\HomeSection;
use Filament\Widgets\Widget;

/** Icon tiles for the most common jobs. Only tiles the user may use are shown; no tiles → no widget. */
class QuickActions extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return self::tiles() !== [];
    }

    /** @return list<array{key: string, label: string, help: string, icon: string, url: string}> */
    public static function tiles(): array
    {
        $clients = HomeSection::query()->where('key', HomeSectionKey::Clients)->first();

        $tiles = [
            ['key' => 'home', 'label' => 'Edit home page', 'help' => 'Sections, videos and texts', 'icon' => 'heroicon-o-home',
                'url' => fn () => HomeSectionResource::getUrl('index'), 'can' => fn () => PermissionMatrix::can('home', 'update')],
            ['key' => 'client', 'label' => 'Add client logo', 'help' => 'Logos in the clients strip', 'icon' => 'heroicon-o-photo',
                'url' => fn () => $clients ? HomeSectionResource::getUrl('edit', ['record' => $clients]) : HomeSectionResource::getUrl('index'),
                'can' => fn () => PermissionMatrix::can('home', 'create')],
            ['key' => 'services', 'label' => 'Edit services', 'help' => 'Categories, services and FAQs', 'icon' => 'heroicon-o-squares-2x2',
                'url' => fn () => ServiceCategoryResource::getUrl('index'), 'can' => fn () => PermissionMatrix::can('services', 'update')],
            ['key' => 'settings', 'label' => 'Site settings', 'help' => 'Contact details, social links, SEO', 'icon' => 'heroicon-o-cog-6-tooth',
                'url' => fn () => ManageSettings::getUrl(), 'can' => fn () => ManageSettings::canAccess()],
            ['key' => 'ui', 'label' => 'Interface texts', 'help' => 'Buttons, menus and labels', 'icon' => 'heroicon-o-language',
                'url' => fn () => UiStringResource::getUrl('index'), 'can' => fn () => PermissionMatrix::can('ui', 'update')],
            ['key' => 'user', 'label' => 'Add user', 'help' => 'Give a colleague access', 'icon' => 'heroicon-o-user-plus',
                'url' => fn () => UserResource::getUrl('create'), 'can' => fn () => PermissionMatrix::can('users', 'create')],
        ];

        return collect($tiles)
            ->filter(fn (array $tile): bool => (bool) ($tile['can'])())
            ->map(fn (array $tile): array => [
                'key' => $tile['key'],
                'label' => __($tile['label']),
                'help' => __($tile['help']),
                'icon' => $tile['icon'],
                'url' => ($tile['url'])(),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return ['tiles' => self::tiles()];
    }
}
