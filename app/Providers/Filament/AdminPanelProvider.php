<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\RoleResource;
use App\Filament\Support\NavGroup;
use App\Http\Controllers\Admin\SwitchAdminLocaleController;
use App\Http\Middleware\RequireHttps;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAdminLocale;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\FontProviders\LocalFontProvider;
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
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public const THEME_CSS = 'css/pixellap-admin.css';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // Adds a 5/min per email+IP limiter on top of Filament's per-IP limiter.
            ->login(Login::class)
            ->brandName('Pixellap')
            ->brandLogo(fn (): string => asset('brand/pixellap-logo.svg'))
            ->darkModeBrandLogo(fn (): string => asset('brand/pixellap-logo-white.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(fn (): string => asset('brand/favicon.svg'))
            ->colors([
                'primary' => Color::hex('#612FE2'),
            ])
            ->darkMode(true)
            // Brand font (Arabic + Latin), served from public/fonts — no build step.
            ->font('RH-Zak', url: asset('fonts/rh-zak.css'), provider: LocalFontProvider::class)
            ->maxContentWidth(MaxWidth::SevenExtraLarge)
            ->sidebarCollapsibleOnDesktop()
            // Bell icon: new contact messages (App\Services\ContactInbox).
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->navigationGroups(array_map(
                fn (string $group): NavigationGroup => NavigationGroup::make()
                    ->label(fn (): string => __($group))
                    ->collapsible(false),
                NavGroup::ordered(),
            ))
            // My profile (name, email, password, photo) from the user menu.
            ->profile(isSimple: false)
            // User menu: name + role header, My profile, theme, the ONLY language switch, sign out.
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn (): string => __('My profile'))
                    ->icon('heroicon-o-user-circle'),
                'language' => MenuItem::make()
                    ->label(fn (): string => SwitchAdminLocaleController::other() === 'ar' ? 'العربية' : 'English')
                    ->icon('heroicon-o-language')
                    ->postAction(fn (): string => route('admin.locale.switch', ['locale' => SwitchAdminLocaleController::other()])),
                'logout' => MenuItem::make()->label(fn (): string => __('Sign out')),
            ])
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            // Our simple roles editor. Registered BEFORE the Shield plugin: Shield adds its own RoleResource
            // unless the panel already lists a "\RoleResource" when the plugin registers (discovery runs later).
            ->resources([
                RoleResource::class,
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
            // Only the app's own widgets (no Account / Filament info widgets).
            ->widgets([])
            // Login: short tagline above the form + a subtle pixel-square background (CSS only).
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): View => view('filament.auth.login-tagline'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): View => view('filament.topbar.view-website'),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
                fn (): View => view('filament.topbar.user-header'),
            )
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
        // Admin theme: plain CSS, no build step (public/css/pixellap-admin.css). Versioned by file time.
        $theme = public_path(self::THEME_CSS);
        FilamentAsset::register([
            Css::make('pixellap-admin', asset(self::THEME_CSS).'?v='.(is_file($theme) ? filemtime($theme) : '0')),
        ], 'app');

        // Admin labels are written in English and translated through lang/ar.json
        // when a user switches the admin UI to Arabic.
        Field::configureUsing(fn (Field $field) => $field->translateLabel());
        Placeholder::configureUsing(fn (Placeholder $field) => $field->translateLabel());
        Column::configureUsing(fn (Column $column) => $column->translateLabel());
        BaseFilter::configureUsing(fn (BaseFilter $filter) => $filter->translateLabel());
        Entry::configureUsing(fn (Entry $entry) => $entry->translateLabel());
    }
}
