<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\RevalidationService;
use App\Support\CacheTags;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;

/**
 * "Good morning, {name}" + today's date, two header actions, then role-aware widgets:
 * StatsOverview (messages KPIs), QuickActions, WebsiteReadiness, LatestMessages, MessagesChart.
 * Each widget decides its own visibility (App\Filament\Support\PermissionMatrix::can()).
 */
class Dashboard extends BaseDashboard
{
    /** Roles allowed to force a full website refresh (and to see the messages chart). */
    public const REFRESH_ROLES = [AppServiceProvider::SUPER_ADMIN_ROLE, 'admin'];

    public function getTitle(): string
    {
        return __('Dashboard');
    }

    public function getHeading(): string
    {
        $name = auth()->user()?->name;
        $first = $name ? (string) str($name)->before(' ') : null;

        return $first ? __(':greeting, :name', ['greeting' => self::greeting(), 'name' => $first]) : self::greeting();
    }

    public function getSubheading(): ?string
    {
        return self::today();
    }

    /** "Good morning" (until 12:00), "Good afternoon" (until 18:00), "Good evening" — in the app time zone. */
    public static function greeting(?Carbon $now = null): string
    {
        $hour = ($now ?? now())->hour;

        return __(match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        });
    }

    /** "Thursday, 26 November 2026" / "الخميس، 26 نوفمبر 2026" */
    public static function today(?Carbon $now = null): string
    {
        $date = ($now ?? now())->copy()->locale(app()->getLocale());

        return app()->getLocale() === 'ar'
            ? $date->translatedFormat('l، j F Y')
            : $date->translatedFormat('l, j F Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewWebsite')
                ->label(__('View website'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (): string => (string) config('pixellap.frontend_url'), shouldOpenInNewTab: true),
            Action::make('refreshWebsiteCache')
                ->label(__('Refresh website cache'))
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-arrow-path')
                ->modalDescription(__('Clears the API cache and asks the website to reload every page. Use it if a change does not appear on the website.'))
                ->visible(fn (): bool => self::canRefresh())
                ->action(function (RevalidationService $revalidation): void {
                    $revalidation->touch(CacheTags::all());

                    Notification::make()
                        ->success()
                        ->title(__('Website refresh requested'))
                        ->body(__('Pages update within a few seconds once the queue worker runs.'))
                        ->send();
                }),
        ];
    }

    public static function canRefresh(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyRole(self::REFRESH_ROLES);
    }
}
