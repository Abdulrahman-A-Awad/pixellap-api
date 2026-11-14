<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Support\NavGroup;
use App\Http\Middleware\RequireHttps;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAdminLocale;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Placeholder;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\Entry;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\SpatieLaravelTranslatablePlugin;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // Adds a 5/min per email+IP limiter on top of Filament's per-IP limiter.
            ->login(Login::class)
            ->brandName('Pixellap')
            ->colors([
                'primary' => Color::hex('#612FE2'),
            ])
            ->darkMode(true)
            // Until brand fonts arrive. Covers Latin + Arabic.
            ->font('IBM Plex Sans Arabic', provider: GoogleFontProvider::class)
            ->sidebarCollapsibleOnDesktop()
            // Bell icon: new contact messages (App\Services\ContactInbox).
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->navigationGroups(array_map(
                fn (string $group): NavigationGroup => NavigationGroup::make()->label(fn (): string => __($group)),
                NavGroup::ordered(),
            ))
            ->userMenuItems([
                MenuItem::make()
                    ->label('العربية / English')
                    ->icon('heroicon-o-language')
                    ->postAction(fn (): string => route('admin.locale.toggle')),
            ])
            ->plugins([
                SpatieLaravelTranslatablePlugin::make()
                    ->defaultLocales(['en', 'ar']),
                FilamentShieldPlugin::make(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                // Production: HTTPS only (redirect GET, refuse the rest).
                RequireHttps::class,
                SecurityHeaders::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                // Must run before the serving event: Filament builds the navigation there.
                SetAdminLocale::class,
                DispatchServingFilamentEvent::class,
            ])
            // Also applied to Livewire requests made from panel pages (idempotent).
            ->middleware([
                RequireHttps::class,
                SetAdminLocale::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        // Admin labels are written in English and translated through lang/ar.json
        // when a user switches the admin UI to Arabic.
        Field::configureUsing(fn (Field $field) => $field->translateLabel());
        Placeholder::configureUsing(fn (Placeholder $field) => $field->translateLabel());
        Column::configureUsing(fn (Column $column) => $column->translateLabel());
        BaseFilter::configureUsing(fn (BaseFilter $filter) => $filter->translateLabel());
        Entry::configureUsing(fn (Entry $entry) => $entry->translateLabel());
    }
}
